"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ApiError, api } from "@/lib/api/client";

type PondStatus =
    | "available"
    | "preparation"
    | "cultivation"
    | "harvest"
    | "maintenance"
    | "inactive";

type Pond = {
    id: number;
    code: string;
    name: string;
    dimensions: {
        length: number;
        width: number;
        depth: number;
    };
    volume: number;
    status: PondStatus;
    notes: string | null;
};

type PondResponse = {
    data: Pond[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};

type PondForm = {
    code: string;
    name: string;
    length: string;
    width: string;
    depth: string;
    status: PondStatus;
    notes: string;
};

const STATUS_LABELS: Record<PondStatus, string> = {
    available: "Tersedia",
    preparation: "Persiapan",
    cultivation: "Budidaya",
    harvest: "Panen",
    maintenance: "Perawatan",
    inactive: "Nonaktif",
};

const STATUS_STYLES: Record<PondStatus, string> = {
    available: "border-sky-400/25 bg-sky-400/10 text-sky-200",
    preparation: "border-amber-400/25 bg-amber-400/10 text-amber-200",
    cultivation: "border-emerald-400/25 bg-emerald-400/10 text-emerald-200",
    harvest: "border-orange-400/25 bg-orange-400/10 text-orange-200",
    maintenance: "border-rose-400/25 bg-rose-400/10 text-rose-200",
    inactive: "border-zinc-400/25 bg-zinc-400/10 text-zinc-300",
};

const STATUS_OPTIONS = Object.keys(STATUS_LABELS) as PondStatus[];

function emptyForm(): PondForm {
    return {
        code: "",
        name: "",
        length: "",
        width: "",
        depth: "",
        status: "available",
        notes: "",
    };
}

function validateForm(form: PondForm): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.code.trim()) {
        errors.code = "Kode kolam wajib diisi.";
    } else if (form.code.trim().length > 30) {
        errors.code = "Kode kolam maksimal 30 karakter.";
    }

    if (!form.name.trim()) {
        errors.name = "Nama kolam wajib diisi.";
    } else if (form.name.trim().length > 100) {
        errors.name = "Nama kolam maksimal 100 karakter.";
    }

    for (const field of ["length", "width", "depth"] as const) {
        const value = Number(form[field]);

        if (!form[field] || !Number.isFinite(value) || value < 0.1) {
            errors[field] = "Ukuran harus berupa angka minimal 0,1.";
        }
    }

    return errors;
}

function getErrorMessage(error: unknown): string {
    return error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat menghubungi API.";
}

function fieldErrorsFrom(error: ApiError): Record<string, string> {
    return Object.fromEntries(
        Object.entries(error.errors ?? {}).map(([field, messages]) => [
            field,
            messages[0] ?? "Input tidak valid.",
        ]),
    );
}

export default function PondsPage() {
    const router = useRouter();
    const [ponds, setPonds] = useState<Pond[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [editingPond, setEditingPond] = useState<Pond | null>(null);
    const [form, setForm] = useState<PondForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    useEffect(() => {
        let active = true;

        async function loadPonds() {
            const token = localStorage.getItem("auth_token");

            if (!token) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PondResponse>(`/ponds?page=${page}`);

                if (!active) {
                    return;
                }

                setPonds(response.data);
                setLastPage(response.meta.last_page);
                setTotal(response.meta.total);
            } catch (requestError) {
                if (!active) {
                    return;
                }

                if (requestError instanceof ApiError && requestError.status === 401) {
                    localStorage.removeItem("auth_token");
                    localStorage.removeItem("auth_user");
                    router.replace("/login");
                    return;
                }

                setError(getErrorMessage(requestError));
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        }

        void loadPonds();

        return () => {
            active = false;
        };
    }, [page, reloadKey, router]);

    function openCreateForm() {
        setEditingPond(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function openEditForm(pond: Pond) {
        setEditingPond(pond);
        setForm({
            code: pond.code,
            name: pond.name,
            length: String(pond.dimensions.length),
            width: String(pond.dimensions.width),
            depth: String(pond.dimensions.depth),
            status: pond.status,
            notes: pond.notes ?? "",
        });
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function closeForm() {
        if (saving) {
            return;
        }

        setFormOpen(false);
        setEditingPond(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
    }

    function handleUnauthorized(requestError: unknown): boolean {
        if (requestError instanceof ApiError && requestError.status === 401) {
            localStorage.removeItem("auth_token");
            localStorage.removeItem("auth_user");
            router.replace("/login");
            return true;
        }

        return false;
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setFormError("");

        const clientErrors = validateForm(form);

        if (Object.keys(clientErrors).length > 0) {
            setFormErrors(clientErrors);
            return;
        }

        setFormErrors({});
        setSaving(true);

        const payload = {
            code: form.code.trim(),
            name: form.name.trim(),
            length: Number(form.length),
            width: Number(form.width),
            depth: Number(form.depth),
            status: form.status,
            notes: form.notes.trim() || null,
        };

        try {
            if (editingPond) {
                await api(`/ponds/${editingPond.id}`, {
                    method: "PUT",
                    body: JSON.stringify(payload),
                });
                setSuccess(`Kolam ${payload.code} berhasil diperbarui.`);
            } else {
                await api("/ponds", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                setSuccess(`Kolam ${payload.code} berhasil dibuat.`);
            }

            setFormOpen(false);
            setEditingPond(null);
            setForm(emptyForm());
            setPage(1);
            setReloadKey((current) => current + 1);
        } catch (requestError) {
            if (handleUnauthorized(requestError)) {
                return;
            }

            setFormError(getErrorMessage(requestError));

            if (requestError instanceof ApiError && requestError.status === 422) {
                setFormErrors(fieldErrorsFrom(requestError));
            }
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(pond: Pond) {
        if (!window.confirm(`Hapus kolam ${pond.code} - ${pond.name}?`)) {
            return;
        }

        setDeletingId(pond.id);
        setError("");
        setSuccess("");

        try {
            await api(`/ponds/${pond.id}`, { method: "DELETE" });
            setSuccess(`Kolam ${pond.code} berhasil dihapus.`);

            if (ponds.length === 1 && page > 1) {
                setPage((current) => current - 1);
            }

            setReloadKey((current) => current + 1);
        } catch (requestError) {
            if (handleUnauthorized(requestError)) {
                return;
            }

            setError(getErrorMessage(requestError));
        } finally {
            setDeletingId(null);
        }
    }

    function refreshPonds() {
        setSuccess("");
        setReloadKey((current) => current + 1);
    }

    const volumePreview =
        Number(form.length) > 0 &&
        Number(form.width) > 0 &&
        Number(form.depth) > 0
            ? (Number(form.length) * Number(form.width) * Number(form.depth)).toFixed(3)
            : "-";

    return (
        <main className="min-h-screen bg-zinc-950 text-white">
            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
                <header className="mb-8 flex flex-col gap-5 border-b border-white/10 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <Link
                            href="/dashboard"
                            className="text-sm font-medium text-emerald-300 transition hover:text-emerald-200"
                        >
                            Lele Management
                        </Link>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                            Manajemen Kolam
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} kolam terdaftar
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshPonds}
                            disabled={loading}
                            className="rounded-lg border border-white/10 px-4 py-2.5 text-sm font-medium text-zinc-200 transition hover:border-white/20 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {loading ? "Memuat..." : "Muat ulang"}
                        </button>
                        <button
                            type="button"
                            onClick={openCreateForm}
                            className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                        >
                            Tambah kolam
                        </button>
                    </div>
                </header>

                {success && (
                    <div
                        role="status"
                        className="mb-5 rounded-lg border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
                    >
                        {success}
                    </div>
                )}

                {error && (
                    <div
                        role="alert"
                        className="mb-5 flex flex-col gap-3 rounded-lg border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p>{error}</p>
                        <button
                            type="button"
                            onClick={refreshPonds}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                {loading && ponds.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-32 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && ponds.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">Belum ada kolam</h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            Tambahkan kolam pertama untuk mulai mengelola kapasitas budidaya.
                        </p>
                        <button
                            type="button"
                            onClick={openCreateForm}
                            className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                        >
                            Tambah kolam
                        </button>
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {ponds.map((pond) => (
                                <article
                                    key={pond.id}
                                    className="grid gap-4 rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-5"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <span className="font-mono text-xs text-zinc-400">
                                                {pond.code}
                                            </span>
                                            <span
                                                className={`rounded-full border px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[pond.status]}`}
                                            >
                                                {STATUS_LABELS[pond.status]}
                                            </span>
                                        </div>
                                        <h2 className="mt-2 truncate text-lg font-semibold">
                                            {pond.name}
                                        </h2>

                                        <dl className="mt-4 grid grid-cols-2 gap-x-5 gap-y-3 text-sm sm:grid-cols-4">
                                            <div>
                                                <dt className="text-xs text-zinc-500">Panjang</dt>
                                                <dd className="mt-1 text-zinc-200">{pond.dimensions.length}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Lebar</dt>
                                                <dd className="mt-1 text-zinc-200">{pond.dimensions.width}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Kedalaman</dt>
                                                <dd className="mt-1 text-zinc-200">{pond.dimensions.depth}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Volume</dt>
                                                <dd className="mt-1 font-medium text-emerald-200">{pond.volume}</dd>
                                            </div>
                                        </dl>

                                        {pond.notes && (
                                            <p className="mt-4 border-t border-white/5 pt-3 text-sm text-zinc-400">
                                                {pond.notes}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-2 border-t border-white/5 pt-3 sm:border-0 sm:pt-0">
                                        <button
                                            type="button"
                                            onClick={() => openEditForm(pond)}
                                            className="rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-zinc-200 transition hover:border-white/20 hover:bg-white/5"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => void handleDelete(pond)}
                                            disabled={deletingId === pond.id}
                                            className="rounded-lg border border-red-400/20 px-3 py-2 text-sm font-medium text-red-200 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {deletingId === pond.id ? "Menghapus..." : "Hapus"}
                                        </button>
                                    </div>
                                </article>
                            ))}
                        </div>

                        <div className="mt-5 flex flex-col gap-3 border-t border-white/10 pt-4 text-sm text-zinc-400 sm:flex-row sm:items-center sm:justify-between">
                            <p>Halaman {page} dari {lastPage}</p>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setPage((current) => Math.max(1, current - 1))}
                                    disabled={page <= 1 || loading}
                                    className="rounded-lg border border-white/10 px-3 py-2 text-zinc-200 transition hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Sebelumnya
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPage((current) => Math.min(lastPage, current + 1))}
                                    disabled={page >= lastPage || loading}
                                    className="rounded-lg border border-white/10 px-3 py-2 text-zinc-200 transition hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Berikutnya
                                </button>
                            </div>
                        </div>
                    </>
                )}
            </div>

            {formOpen && (
                <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/75 p-4 sm:items-center">
                    <section
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="pond-form-title"
                        className="my-4 w-full max-w-2xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="pond-form-title" className="text-lg font-semibold">
                                    {editingPond ? "Ubah kolam" : "Tambah kolam"}
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    Isi identitas, ukuran, dan status kolam.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={closeForm}
                                disabled={saving}
                                aria-label="Tutup form"
                                className="rounded-md px-2 py-1 text-xl leading-none text-zinc-400 hover:bg-white/5 hover:text-white disabled:opacity-50"
                            >
                                ×
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5 px-5 py-5 sm:px-6">
                            {formError && (
                                <div
                                    role="alert"
                                    className="rounded-lg border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                                >
                                    {formError}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="pond-code" label="Kode kolam" error={formErrors.code}>
                                    <input
                                        id="pond-code"
                                        value={form.code}
                                        onChange={(event) => setForm({ ...form, code: event.target.value })}
                                        maxLength={30}
                                        required
                                        autoFocus
                                        className={inputClass(!!formErrors.code)}
                                    />
                                </FormField>
                                <FormField id="pond-name" label="Nama kolam" error={formErrors.name}>
                                    <input
                                        id="pond-name"
                                        value={form.name}
                                        onChange={(event) => setForm({ ...form, name: event.target.value })}
                                        maxLength={100}
                                        required
                                        className={inputClass(!!formErrors.name)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <FormField id="pond-length" label="Panjang" error={formErrors.length}>
                                    <input
                                        id="pond-length"
                                        type="number"
                                        min="0.1"
                                        step="0.01"
                                        value={form.length}
                                        onChange={(event) => setForm({ ...form, length: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.length)}
                                    />
                                </FormField>
                                <FormField id="pond-width" label="Lebar" error={formErrors.width}>
                                    <input
                                        id="pond-width"
                                        type="number"
                                        min="0.1"
                                        step="0.01"
                                        value={form.width}
                                        onChange={(event) => setForm({ ...form, width: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.width)}
                                    />
                                </FormField>
                                <FormField id="pond-depth" label="Kedalaman" error={formErrors.depth}>
                                    <input
                                        id="pond-depth"
                                        type="number"
                                        min="0.1"
                                        step="0.01"
                                        value={form.depth}
                                        onChange={(event) => setForm({ ...form, depth: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.depth)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="pond-status" label="Status" error={formErrors.status}>
                                    <select
                                        id="pond-status"
                                        value={form.status}
                                        onChange={(event) => setForm({ ...form, status: event.target.value as PondStatus })}
                                        className={inputClass(!!formErrors.status)}
                                    >
                                        {STATUS_OPTIONS.map((status) => (
                                            <option key={status} value={status}>
                                                {STATUS_LABELS[status]}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                                <div className="rounded-lg border border-white/10 bg-black/20 px-4 py-3">
                                    <p className="text-xs text-zinc-500">Perkiraan volume</p>
                                    <p className="mt-1 font-medium text-emerald-200">{volumePreview}</p>
                                </div>
                            </div>

                            <FormField id="pond-notes" label="Catatan" error={formErrors.notes}>
                                <textarea
                                    id="pond-notes"
                                    value={form.notes}
                                    onChange={(event) => setForm({ ...form, notes: event.target.value })}
                                    rows={3}
                                    className={`${inputClass(!!formErrors.notes)} resize-y`}
                                />
                            </FormField>

                            <div className="flex flex-col-reverse gap-2 border-t border-white/10 pt-4 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    onClick={closeForm}
                                    disabled={saving}
                                    className="rounded-lg border border-white/10 px-4 py-2.5 text-sm font-medium text-zinc-200 transition hover:bg-white/5 disabled:opacity-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving ? "Menyimpan..." : "Simpan kolam"}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            )}
        </main>
    );
}

function FormField({
    id,
    label,
    error,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <label htmlFor={id} className="mb-2 block text-sm font-medium text-zinc-300">
                {label}
            </label>
            {children}
            {error && (
                <p className="mt-1.5 text-xs text-red-300" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

function inputClass(hasError: boolean): string {
    return `w-full rounded-lg border bg-black/30 px-3 py-2.5 text-sm text-white outline-none transition focus:border-emerald-400/60 ${hasError ? "border-red-400/40" : "border-white/10"}`;
}
