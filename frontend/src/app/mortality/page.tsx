"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ApiError, api } from "@/lib/api/client";

type CycleStatus = "planned" | "active" | "harvest" | "completed" | "cancelled";

type FishCycleOption = {
    id: number;
    code: string;
    status: CycleStatus;
    pond?: {
        id: number;
        code: string;
        name: string;
    };
};

type Mortality = {
    id: number;
    fish_cycle: {
        id: number;
        code: string;
    };
    mortality_date: string;
    quantity: number;
    cause: string | null;
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

type MortalityForm = {
    fish_cycle_id: string;
    mortality_date: string;
    quantity: string;
    cause: string;
    notes: string;
};

const CYCLE_STATUS_LABELS: Record<CycleStatus, string> = {
    planned: "Direncanakan",
    active: "Berjalan",
    harvest: "Panen",
    completed: "Selesai",
    cancelled: "Dibatalkan",
};

const CYCLE_STATUS_STYLES: Record<CycleStatus, string> = {
    planned: "border-amber-400/25 bg-amber-400/10 text-amber-200",
    active: "border-emerald-400/25 bg-emerald-400/10 text-emerald-200",
    harvest: "border-orange-400/25 bg-orange-400/10 text-orange-200",
    completed: "border-sky-400/25 bg-sky-400/10 text-sky-200",
    cancelled: "border-zinc-400/25 bg-zinc-400/10 text-zinc-300",
};

function today(): string {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10);
}

function emptyForm(): MortalityForm {
    return {
        fish_cycle_id: "",
        mortality_date: today(),
        quantity: "",
        cause: "",
        notes: "",
    };
}

function displayDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split("-");

    return `${day}/${month}/${year}`;
}

function formatCount(value: number): string {
    return new Intl.NumberFormat("id-ID").format(value);
}

function validateForm(form: MortalityForm): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.fish_cycle_id) {
        errors.fish_cycle_id = "Siklus budidaya wajib dipilih.";
    }

    if (!form.mortality_date) {
        errors.mortality_date = "Tanggal mortalitas wajib diisi.";
    }

    const quantity = Number(form.quantity);

    if (
        !form.quantity ||
        !Number.isInteger(quantity) ||
        quantity < 1
    ) {
        errors.quantity = "Jumlah ikan mati harus bilangan bulat minimal 1.";
    }

    if (form.cause.length > 100) {
        errors.cause = "Penyebab maksimal 100 karakter.";
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

async function loadAllCycles(): Promise<FishCycleOption[]> {
    const cycles: FishCycleOption[] = [];
    let currentPage = 1;
    let lastPage = 1;

    do {
        const response = await api<PaginatedResponse<FishCycleOption>>(
            `/fish-cycles?page=${currentPage}`,
        );

        cycles.push(...response.data);
        lastPage = response.meta.last_page;
        currentPage += 1;
    } while (currentPage <= lastPage);

    return cycles;
}

export default function MortalityPage() {
    const router = useRouter();
    const [mortalities, setMortalities] = useState<Mortality[]>([]);
    const [cycles, setCycles] = useState<FishCycleOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [cyclesLoading, setCyclesLoading] = useState(true);
    const [error, setError] = useState("");
    const [cyclesError, setCyclesError] = useState("");
    const [success, setSuccess] = useState("");
    const [search, setSearch] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [editingMortality, setEditingMortality] = useState<Mortality | null>(null);
    const [form, setForm] = useState<MortalityForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    useEffect(() => {
        let active = true;

        async function loadMortalities() {
            if (!localStorage.getItem("auth_token")) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PaginatedResponse<Mortality>>(
                    `/mortalities?page=${page}`,
                );

                if (!active) {
                    return;
                }

                setMortalities(response.data);
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

        void loadMortalities();

        return () => {
            active = false;
        };
    }, [page, reloadKey, router]);

    useEffect(() => {
        let active = true;

        async function loadCycles() {
            if (!localStorage.getItem("auth_token")) {
                return;
            }

            setCyclesLoading(true);
            setCyclesError("");

            try {
                const response = await loadAllCycles();

                if (active) {
                    setCycles(response);
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

                setCyclesError(getErrorMessage(requestError));
            } finally {
                if (active) {
                    setCyclesLoading(false);
                }
            }
        }

        void loadCycles();

        return () => {
            active = false;
        };
    }, [router]);

    const visibleMortalities = mortalities.filter((mortality) => {
        const query = search.trim().toLowerCase();
        const cycle = cycles.find((option) => option.id === mortality.fish_cycle.id);

        return !query || [
            mortality.fish_cycle.code,
            cycle?.pond?.name ?? "",
            cycle?.pond?.code ?? "",
            mortality.cause ?? "",
            mortality.notes ?? "",
        ].some((value) => value.toLowerCase().includes(query));
    });

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
        setEditingMortality(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function openEditForm(mortality: Mortality) {
        setEditingMortality(mortality);
        setForm({
            fish_cycle_id: String(mortality.fish_cycle.id),
            mortality_date: mortality.mortality_date.slice(0, 10),
            quantity: String(mortality.quantity),
            cause: mortality.cause ?? "",
            notes: mortality.notes ?? "",
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
        setEditingMortality(null);
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
            fish_cycle_id: Number(form.fish_cycle_id),
            mortality_date: form.mortality_date,
            quantity: Number(form.quantity),
            cause: form.cause.trim() || null,
            notes: form.notes.trim() || null,
        };

        try {
            if (editingMortality) {
                await api(`/mortalities/${editingMortality.id}`, {
                    method: "PUT",
                    body: JSON.stringify(payload),
                });
                setSuccess("Catatan mortalitas berhasil diperbarui.");
            } else {
                await api("/mortalities", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                setSuccess("Catatan mortalitas berhasil dibuat.");
            }

            setFormOpen(false);
            setEditingMortality(null);
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

    async function handleDelete(mortality: Mortality) {
        if (!window.confirm(`Hapus catatan mortalitas ${mortality.quantity} ekor?`)) {
            return;
        }

        setDeletingId(mortality.id);
        setError("");
        setSuccess("");

        try {
            await api(`/mortalities/${mortality.id}`, { method: "DELETE" });
            setSuccess("Catatan mortalitas berhasil dihapus.");

            if (mortalities.length === 1 && page > 1) {
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

    function refreshMortalities() {
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
                            Catatan Mortalitas
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} catatan mortalitas
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshMortalities}
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
                            Catat mortalitas
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
                            onClick={refreshMortalities}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                <div className="mb-4 max-w-sm">
                    <label htmlFor="mortality-search" className="sr-only">
                        Cari catatan mortalitas
                    </label>
                    <input
                        id="mortality-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari siklus atau penyebab"
                        className="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-emerald-400/60"
                    />
                </div>

                {loading && mortalities.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-32 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && visibleMortalities.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">
                            {search ? "Catatan tidak ditemukan" : "Belum ada catatan mortalitas"}
                        </h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            {search
                                ? "Coba kata kunci lain."
                                : "Catat jumlah ikan mati pada siklus budidaya."}
                        </p>
                        {!search && (
                            <button
                                type="button"
                                onClick={openCreateForm}
                                className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                            >
                                Catat mortalitas
                            </button>
                        )}
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {visibleMortalities.map((mortality) => {
                                const cycle = cycles.find(
                                    (option) => option.id === mortality.fish_cycle.id,
                                );

                                return (
                                    <article
                                        key={mortality.id}
                                        className="grid gap-4 rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-5"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-3">
                                                <span className="font-mono text-xs text-zinc-400">
                                                    {mortality.fish_cycle.code}
                                                </span>
                                                {cycle && (
                                                    <span
                                                        className={`rounded-full border px-2.5 py-1 text-xs font-medium ${CYCLE_STATUS_STYLES[cycle.status]}`}
                                                    >
                                                        {CYCLE_STATUS_LABELS[cycle.status]}
                                                    </span>
                                                )}
                                                <span className="text-xs text-zinc-500">
                                                    {displayDate(mortality.mortality_date)}
                                                </span>
                                            </div>
                                            <h2 className="mt-2 text-lg font-semibold">
                                                {formatCount(mortality.quantity)} ekor
                                            </h2>
                                            {cycle?.pond && (
                                                <p className="mt-1 text-sm text-zinc-400">
                                                    {cycle.pond.name} · {cycle.pond.code}
                                                </p>
                                            )}
                                            {mortality.cause && (
                                                <p className="mt-3 text-sm text-zinc-300">
                                                    Penyebab: {mortality.cause}
                                                </p>
                                            )}
                                            {mortality.notes && (
                                                <p className="mt-3 border-t border-white/5 pt-3 text-sm text-zinc-400">
                                                    {mortality.notes}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex gap-2 border-t border-white/5 pt-3 sm:border-0 sm:pt-0">
                                            <button
                                                type="button"
                                                onClick={() => openEditForm(mortality)}
                                                className="rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-zinc-200 transition hover:border-white/20 hover:bg-white/5"
                                            >
                                                Ubah
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => void handleDelete(mortality)}
                                                disabled={deletingId === mortality.id}
                                                className="rounded-lg border border-red-400/20 px-3 py-2 text-sm font-medium text-red-200 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {deletingId === mortality.id ? "Menghapus..." : "Hapus"}
                                            </button>
                                        </div>
                                    </article>
                                );
                            })}
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
                        aria-labelledby="mortality-form-title"
                        className="my-4 w-full max-w-2xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="mortality-form-title" className="text-lg font-semibold">
                                    {editingMortality ? "Ubah catatan mortalitas" : "Catat mortalitas"}
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    {editingMortality
                                        ? "Backend saat ini belum mengaktifkan koreksi catatan mortalitas."
                                        : "Catat jumlah ikan mati pada siklus budidaya."}
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

                            {cyclesError && (
                                <div
                                    role="alert"
                                    className="rounded-lg border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                                >
                                    Gagal memuat siklus: {cyclesError}
                                </div>
                            )}

                            <FormField id="mortality-cycle" label="Siklus budidaya" error={formErrors.fish_cycle_id}>
                                <select
                                    id="mortality-cycle"
                                    value={form.fish_cycle_id}
                                    onChange={(event) => setForm({ ...form, fish_cycle_id: event.target.value })}
                                    required
                                    disabled={cyclesLoading || cycles.length === 0}
                                    className={inputClass(!!formErrors.fish_cycle_id)}
                                >
                                    <option value="">
                                        {cyclesLoading ? "Memuat siklus..." : "Pilih siklus"}
                                    </option>
                                    {cycles.map((cycle) => (
                                        <option key={cycle.id} value={cycle.id}>
                                            {cycle.code}
                                            {cycle.pond ? ` · ${cycle.pond.name}` : ""}
                                            {` · ${CYCLE_STATUS_LABELS[cycle.status]}`}
                                        </option>
                                    ))}
                                </select>
                                {!cyclesLoading && cycles.length === 0 && !cyclesError && (
                                    <p className="mt-1.5 text-xs text-amber-200">
                                        Belum ada siklus budidaya.
                                    </p>
                                )}
                            </FormField>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="mortality-date" label="Tanggal mortalitas" error={formErrors.mortality_date}>
                                    <input
                                        id="mortality-date"
                                        type="date"
                                        value={form.mortality_date}
                                        onChange={(event) => setForm({ ...form, mortality_date: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.mortality_date)}
                                    />
                                </FormField>
                                <FormField id="mortality-quantity" label="Jumlah ikan mati" error={formErrors.quantity}>
                                    <input
                                        id="mortality-quantity"
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={form.quantity}
                                        onChange={(event) => setForm({ ...form, quantity: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.quantity)}
                                    />
                                </FormField>
                            </div>

                            <FormField id="mortality-cause" label="Penyebab (opsional)" error={formErrors.cause}>
                                <input
                                    id="mortality-cause"
                                    value={form.cause}
                                    onChange={(event) => setForm({ ...form, cause: event.target.value })}
                                    maxLength={100}
                                    className={inputClass(!!formErrors.cause)}
                                />
                            </FormField>

                            <FormField id="mortality-notes" label="Catatan (opsional)" error={formErrors.notes}>
                                <textarea
                                    id="mortality-notes"
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
                                    disabled={saving || cyclesLoading || cycles.length === 0}
                                    className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving ? "Menyimpan..." : editingMortality ? "Simpan perubahan" : "Simpan mortalitas"}
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
