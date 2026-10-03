"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ApiError, api } from "@/lib/api/client";

type CycleStatus = "planned" | "active" | "harvest" | "completed" | "cancelled";

type PondOption = {
    id: number;
    code: string;
    name: string;
};

type FishCycle = {
    id: number;
    code: string;
    pond: PondOption;
    start_date: string;
    target_harvest_date: string | null;
    initial_fish_count: number;
    seed_size: number | null;
    status: CycleStatus;
    notes: string | null;
};

type PaginatedResponse<T> = {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};

type CycleForm = {
    pond_id: string;
    code: string;
    start_date: string;
    target_harvest_date: string;
    initial_fish_count: string;
    seed_size: string;
    status: CycleStatus;
    notes: string;
};

const STATUS_LABELS: Record<CycleStatus, string> = {
    planned: "Direncanakan",
    active: "Berjalan",
    harvest: "Panen",
    completed: "Selesai",
    cancelled: "Dibatalkan",
};

const STATUS_STYLES: Record<CycleStatus, string> = {
    planned: "border-amber-400/25 bg-amber-400/10 text-amber-200",
    active: "border-emerald-400/25 bg-emerald-400/10 text-emerald-200",
    harvest: "border-orange-400/25 bg-orange-400/10 text-orange-200",
    completed: "border-sky-400/25 bg-sky-400/10 text-sky-200",
    cancelled: "border-zinc-400/25 bg-zinc-400/10 text-zinc-300",
};

const STATUS_OPTIONS = Object.keys(STATUS_LABELS) as CycleStatus[];

function emptyForm(): CycleForm {
    return {
        pond_id: "",
        code: "",
        start_date: "",
        target_harvest_date: "",
        initial_fish_count: "",
        seed_size: "",
        status: "planned",
        notes: "",
    };
}

function dateInputValue(value: string | null): string {
    return value?.slice(0, 10) ?? "";
}

function displayDate(value: string | null): string {
    if (!value) {
        return "-";
    }

    const [year, month, day] = value.slice(0, 10).split("-");

    return `${day}/${month}/${year}`;
}

function formatCount(value: number): string {
    return new Intl.NumberFormat("id-ID").format(value);
}

function validateForm(form: CycleForm): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.pond_id) {
        errors.pond_id = "Kolam wajib dipilih.";
    }

    if (!form.code.trim()) {
        errors.code = "Kode siklus wajib diisi.";
    } else if (form.code.trim().length > 50) {
        errors.code = "Kode siklus maksimal 50 karakter.";
    }

    if (!form.start_date) {
        errors.start_date = "Tanggal mulai wajib diisi.";
    }

    if (
        form.target_harvest_date &&
        form.start_date &&
        form.target_harvest_date < form.start_date
    ) {
        errors.target_harvest_date = "Tanggal panen tidak boleh sebelum tanggal mulai.";
    }

    const initialCount = Number(form.initial_fish_count);

    if (
        !form.initial_fish_count ||
        !Number.isInteger(initialCount) ||
        initialCount < 1
    ) {
        errors.initial_fish_count = "Jumlah tebar harus bilangan bulat minimal 1.";
    }

    if (form.seed_size) {
        const seedSize = Number(form.seed_size);

        if (!Number.isFinite(seedSize) || seedSize < 0.01) {
            errors.seed_size = "Ukuran benih minimal 0,01.";
        } else if (!/^\d+(\.\d{1,2})?$/.test(form.seed_size)) {
            errors.seed_size = "Ukuran benih maksimal dua angka desimal.";
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

async function loadAllPonds(): Promise<PondOption[]> {
    const ponds: PondOption[] = [];
    let currentPage = 1;
    let lastPage = 1;

    do {
        const response = await api<PaginatedResponse<PondOption>>(
            `/ponds?page=${currentPage}`,
        );

        ponds.push(...response.data);
        lastPage = response.meta.last_page;
        currentPage += 1;
    } while (currentPage <= lastPage);

    return ponds;
}

export default function FishCyclesPage() {
    const router = useRouter();
    const [cycles, setCycles] = useState<FishCycle[]>([]);
    const [ponds, setPonds] = useState<PondOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [pondsLoading, setPondsLoading] = useState(true);
    const [error, setError] = useState("");
    const [pondsError, setPondsError] = useState("");
    const [success, setSuccess] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [editingCycle, setEditingCycle] = useState<FishCycle | null>(null);
    const [form, setForm] = useState<CycleForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    useEffect(() => {
        let active = true;

        async function loadCycles() {
            if (!localStorage.getItem("auth_token")) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PaginatedResponse<FishCycle>>(
                    `/fish-cycles?page=${page}`,
                );

                if (!active) {
                    return;
                }

                setCycles(response.data);
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

        void loadCycles();

        return () => {
            active = false;
        };
    }, [page, reloadKey, router]);

    useEffect(() => {
        let active = true;

        async function loadPondOptions() {
            if (!localStorage.getItem("auth_token")) {
                return;
            }

            setPondsLoading(true);
            setPondsError("");

            try {
                const response = await loadAllPonds();

                if (active) {
                    setPonds(response);
                }
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

                setPondsError(getErrorMessage(requestError));
            } finally {
                if (active) {
                    setPondsLoading(false);
                }
            }
        }

        void loadPondOptions();

        return () => {
            active = false;
        };
    }, [router]);

    function handleUnauthorized(requestError: unknown): boolean {
        if (requestError instanceof ApiError && requestError.status === 401) {
            localStorage.removeItem("auth_token");
            localStorage.removeItem("auth_user");
            router.replace("/login");
            return true;
        }

        return false;
    }

    function openCreateForm() {
        setEditingCycle(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function openEditForm(cycle: FishCycle) {
        setEditingCycle(cycle);
        setForm({
            pond_id: String(cycle.pond.id),
            code: cycle.code,
            start_date: dateInputValue(cycle.start_date),
            target_harvest_date: dateInputValue(cycle.target_harvest_date),
            initial_fish_count: String(cycle.initial_fish_count),
            seed_size: cycle.seed_size === null ? "" : String(cycle.seed_size),
            status: cycle.status,
            notes: cycle.notes ?? "",
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
        setEditingCycle(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
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
            pond_id: Number(form.pond_id),
            code: form.code.trim(),
            start_date: form.start_date,
            target_harvest_date: form.target_harvest_date || null,
            initial_fish_count: Number(form.initial_fish_count),
            seed_size: form.seed_size ? Number(form.seed_size) : null,
            status: form.status,
            notes: form.notes.trim() || null,
        };

        try {
            if (editingCycle) {
                await api(`/fish-cycles/${editingCycle.id}`, {
                    method: "PUT",
                    body: JSON.stringify(payload),
                });
                setSuccess(`Siklus ${payload.code} berhasil diperbarui.`);
            } else {
                await api("/fish-cycles", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                setSuccess(`Siklus ${payload.code} berhasil dibuat.`);
            }

            setFormOpen(false);
            setEditingCycle(null);
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

    async function handleDelete(cycle: FishCycle) {
        if (!window.confirm(`Hapus siklus ${cycle.code} di ${cycle.pond.name}?`)) {
            return;
        }

        setDeletingId(cycle.id);
        setError("");
        setSuccess("");

        try {
            await api(`/fish-cycles/${cycle.id}`, { method: "DELETE" });
            setSuccess(`Siklus ${cycle.code} berhasil dihapus.`);

            if (cycles.length === 1 && page > 1) {
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

    function refreshCycles() {
        setSuccess("");
        setReloadKey((current) => current + 1);
    }

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
                            Siklus Budidaya
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} siklus terdaftar
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshCycles}
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
                            Tambah siklus
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
                            onClick={refreshCycles}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                {loading && cycles.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-36 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && cycles.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">Belum ada siklus budidaya</h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            Buat siklus untuk mencatat penebaran dan masa budidaya ikan.
                        </p>
                        <button
                            type="button"
                            onClick={openCreateForm}
                            className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                        >
                            Tambah siklus
                        </button>
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {cycles.map((cycle) => (
                                <article
                                    key={cycle.id}
                                    className="grid gap-4 rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-5"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <span className="font-mono text-xs text-zinc-400">
                                                {cycle.code}
                                            </span>
                                            <span
                                                className={`rounded-full border px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[cycle.status]}`}
                                            >
                                                {STATUS_LABELS[cycle.status]}
                                            </span>
                                        </div>
                                        <h2 className="mt-2 truncate text-lg font-semibold">
                                            {cycle.pond.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-zinc-400">
                                            Kolam {cycle.pond.code}
                                        </p>

                                        <dl className="mt-4 grid grid-cols-2 gap-x-5 gap-y-3 text-sm sm:grid-cols-4">
                                            <div>
                                                <dt className="text-xs text-zinc-500">Tanggal mulai</dt>
                                                <dd className="mt-1 text-zinc-200">{displayDate(cycle.start_date)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Target panen</dt>
                                                <dd className="mt-1 text-zinc-200">{displayDate(cycle.target_harvest_date)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Jumlah tebar</dt>
                                                <dd className="mt-1 text-zinc-200">{formatCount(cycle.initial_fish_count)} ekor</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Ukuran benih</dt>
                                                <dd className="mt-1 text-zinc-200">{cycle.seed_size ?? "-"}</dd>
                                            </div>
                                        </dl>

                                        {cycle.notes && (
                                            <p className="mt-4 border-t border-white/5 pt-3 text-sm text-zinc-400">
                                                {cycle.notes}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-2 border-t border-white/5 pt-3 sm:border-0 sm:pt-0">
                                        <button
                                            type="button"
                                            onClick={() => openEditForm(cycle)}
                                            className="rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-zinc-200 transition hover:border-white/20 hover:bg-white/5"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => void handleDelete(cycle)}
                                            disabled={deletingId === cycle.id}
                                            className="rounded-lg border border-red-400/20 px-3 py-2 text-sm font-medium text-red-200 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {deletingId === cycle.id ? "Menghapus..." : "Hapus"}
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
                        aria-labelledby="cycle-form-title"
                        className="my-4 w-full max-w-2xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="cycle-form-title" className="text-lg font-semibold">
                                    {editingCycle ? "Ubah siklus" : "Tambah siklus"}
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    Catat kolam, tanggal, jumlah tebar, dan status siklus.
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

                            {pondsError && (
                                <div
                                    role="alert"
                                    className="rounded-lg border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                                >
                                    Gagal memuat kolam: {pondsError}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="cycle-code" label="Kode siklus" error={formErrors.code}>
                                    <input
                                        id="cycle-code"
                                        value={form.code}
                                        onChange={(event) => setForm({ ...form, code: event.target.value })}
                                        maxLength={50}
                                        required
                                        autoFocus
                                        className={inputClass(!!formErrors.code)}
                                    />
                                </FormField>
                                <FormField id="cycle-pond" label="Kolam" error={formErrors.pond_id}>
                                    <select
                                        id="cycle-pond"
                                        value={form.pond_id}
                                        onChange={(event) => setForm({ ...form, pond_id: event.target.value })}
                                        required
                                        disabled={pondsLoading || ponds.length === 0}
                                        className={inputClass(!!formErrors.pond_id)}
                                    >
                                        <option value="">
                                            {pondsLoading ? "Memuat kolam..." : "Pilih kolam"}
                                        </option>
                                        {ponds.map((pond) => (
                                            <option key={pond.id} value={pond.id}>
                                                {pond.code} - {pond.name}
                                            </option>
                                        ))}
                                    </select>
                                    {!pondsLoading && ponds.length === 0 && !pondsError && (
                                        <p className="mt-1.5 text-xs text-amber-200">
                                            Belum ada kolam. Tambahkan kolam terlebih dahulu.
                                        </p>
                                    )}
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="cycle-start-date" label="Tanggal mulai" error={formErrors.start_date}>
                                    <input
                                        id="cycle-start-date"
                                        type="date"
                                        value={form.start_date}
                                        onChange={(event) => setForm({ ...form, start_date: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.start_date)}
                                    />
                                </FormField>
                                <FormField id="cycle-target-date" label="Target panen" error={formErrors.target_harvest_date}>
                                    <input
                                        id="cycle-target-date"
                                        type="date"
                                        min={form.start_date || undefined}
                                        value={form.target_harvest_date}
                                        onChange={(event) => setForm({ ...form, target_harvest_date: event.target.value })}
                                        className={inputClass(!!formErrors.target_harvest_date)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <FormField id="cycle-initial-count" label="Jumlah tebar" error={formErrors.initial_fish_count}>
                                    <input
                                        id="cycle-initial-count"
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={form.initial_fish_count}
                                        onChange={(event) => setForm({ ...form, initial_fish_count: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.initial_fish_count)}
                                    />
                                </FormField>
                                <FormField id="cycle-seed-size" label="Ukuran benih" error={formErrors.seed_size}>
                                    <input
                                        id="cycle-seed-size"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={form.seed_size}
                                        onChange={(event) => setForm({ ...form, seed_size: event.target.value })}
                                        className={inputClass(!!formErrors.seed_size)}
                                    />
                                </FormField>
                                <FormField id="cycle-status" label="Status" error={formErrors.status}>
                                    <select
                                        id="cycle-status"
                                        value={form.status}
                                        onChange={(event) => setForm({ ...form, status: event.target.value as CycleStatus })}
                                        className={inputClass(!!formErrors.status)}
                                    >
                                        {STATUS_OPTIONS.map((status) => (
                                            <option key={status} value={status}>
                                                {STATUS_LABELS[status]}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            </div>

                            <FormField id="cycle-notes" label="Catatan" error={formErrors.notes}>
                                <textarea
                                    id="cycle-notes"
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
                                    disabled={saving || pondsLoading || ponds.length === 0}
                                    className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving ? "Menyimpan..." : "Simpan siklus"}
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
