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
    initial_fish_count: number;
    pond?: {
        id: number;
        code: string;
        name: string;
    };
};

type Harvest = {
    id: number;
    fish_cycle: {
        id: number;
        code: string;
    };
    harvest_date: string;
    total_fish: number;
    total_weight: number;
    average_weight: number;
    selling_price_per_kg: number;
    estimated_revenue: number;
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

type HarvestForm = {
    fish_cycle_id: string;
    harvest_date: string;
    total_fish: string;
    total_weight: string;
    selling_price_per_kg: string;
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

function today(): string {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10);
}

function emptyForm(): HarvestForm {
    return {
        fish_cycle_id: "",
        harvest_date: today(),
        total_fish: "",
        total_weight: "",
        selling_price_per_kg: "",
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

function formatMeasure(value: number): string {
    return new Intl.NumberFormat("id-ID", {
        maximumFractionDigits: 2,
    }).format(value);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 2,
    }).format(value);
}

function validateForm(form: HarvestForm): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.fish_cycle_id) {
        errors.fish_cycle_id = "Siklus budidaya wajib dipilih.";
    }

    if (!form.harvest_date) {
        errors.harvest_date = "Tanggal panen wajib diisi.";
    }

    const totalFish = Number(form.total_fish);

    if (!form.total_fish || !Number.isInteger(totalFish) || totalFish < 1) {
        errors.total_fish = "Jumlah ikan harus bilangan bulat minimal 1.";
    }

    const totalWeight = Number(form.total_weight);

    if (!form.total_weight || !Number.isFinite(totalWeight) || totalWeight <= 0) {
        errors.total_weight = "Total berat harus lebih besar dari 0.";
    }

    const sellingPrice = Number(form.selling_price_per_kg);

    if (
        form.selling_price_per_kg === "" ||
        !Number.isFinite(sellingPrice) ||
        sellingPrice < 0
    ) {
        errors.selling_price_per_kg = "Harga jual tidak boleh negatif.";
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

export default function HarvestsPage() {
    const router = useRouter();
    const [harvests, setHarvests] = useState<Harvest[]>([]);
    const [cycles, setCycles] = useState<FishCycleOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [cyclesReloadKey, setCyclesReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [cyclesLoading, setCyclesLoading] = useState(true);
    const [error, setError] = useState("");
    const [cyclesError, setCyclesError] = useState("");
    const [success, setSuccess] = useState("");
    const [search, setSearch] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [form, setForm] = useState<HarvestForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;

        async function loadHarvests() {
            if (!localStorage.getItem("auth_token")) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PaginatedResponse<Harvest>>(
                    `/harvests?page=${page}`,
                );

                if (!active) {
                    return;
                }

                setHarvests(response.data);
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

        void loadHarvests();

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
    }, [cyclesReloadKey, router]);

    const visibleHarvests = harvests.filter((harvest) => {
        const query = search.trim().toLowerCase();
        const cycle = cycles.find((option) => option.id === harvest.fish_cycle.id);

        return !query || [
            harvest.fish_cycle.code,
            cycle?.pond?.name ?? "",
            cycle?.pond?.code ?? "",
            harvest.notes ?? "",
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
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function closeForm() {
        if (saving) {
            return;
        }

        setFormOpen(false);
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
            harvest_date: form.harvest_date,
            total_fish: Number(form.total_fish),
            total_weight: Number(form.total_weight),
            selling_price_per_kg: Number(form.selling_price_per_kg),
            notes: form.notes.trim() || null,
        };

        try {
            await api("/harvests", {
                method: "POST",
                body: JSON.stringify(payload),
            });

            setSuccess("Data panen berhasil dibuat.");
            setFormOpen(false);
            setForm(emptyForm());
            setPage(1);
            setReloadKey((current) => current + 1);
            setCyclesReloadKey((current) => current + 1);
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

    function refreshHarvests() {
        setSuccess("");
        setReloadKey((current) => current + 1);
        setCyclesReloadKey((current) => current + 1);
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
                            Panen
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} catatan panen
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshHarvests}
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
                            Catat panen
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
                            onClick={refreshHarvests}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                <div className="mb-4 max-w-sm">
                    <label htmlFor="harvest-search" className="sr-only">
                        Cari catatan panen
                    </label>
                    <input
                        id="harvest-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari siklus atau catatan"
                        className="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-emerald-400/60"
                    />
                </div>

                {loading && harvests.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-40 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && visibleHarvests.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">
                            {search ? "Catatan tidak ditemukan" : "Belum ada catatan panen"}
                        </h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            {search
                                ? "Coba kata kunci lain."
                                : "Catat hasil panen dari siklus budidaya."}
                        </p>
                        {!search && (
                            <button
                                type="button"
                                onClick={openCreateForm}
                                className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                            >
                                Catat panen
                            </button>
                        )}
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {visibleHarvests.map((harvest) => {
                                const cycle = cycles.find(
                                    (option) => option.id === harvest.fish_cycle.id,
                                );

                                return (
                                    <article
                                        key={harvest.id}
                                        className="rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                    >
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <span className="font-mono text-xs text-zinc-400">
                                                        {harvest.fish_cycle.code}
                                                    </span>
                                                    {cycle && (
                                                        <span
                                                            className={`rounded-full border px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[cycle.status]}`}
                                                        >
                                                            {STATUS_LABELS[cycle.status]}
                                                        </span>
                                                    )}
                                                    <span className="text-xs text-zinc-500">
                                                        {displayDate(harvest.harvest_date)}
                                                    </span>
                                                </div>
                                                {cycle?.pond && (
                                                    <p className="mt-2 text-sm text-zinc-300">
                                                        {cycle.pond.name} · {cycle.pond.code}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <dl className="mt-4 grid grid-cols-2 gap-x-5 gap-y-3 text-sm sm:grid-cols-3 lg:grid-cols-5">
                                            <div>
                                                <dt className="text-xs text-zinc-500">Total ikan</dt>
                                                <dd className="mt-1 text-zinc-200">{formatCount(harvest.total_fish)} ekor</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Total berat</dt>
                                                <dd className="mt-1 text-zinc-200">{formatMeasure(harvest.total_weight)} kg</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Rata-rata berat</dt>
                                                <dd className="mt-1 text-zinc-200">{formatMeasure(harvest.average_weight)} g/ekor</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Harga per kg</dt>
                                                <dd className="mt-1 text-zinc-200">{formatMoney(harvest.selling_price_per_kg)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Estimasi revenue</dt>
                                                <dd className="mt-1 font-medium text-orange-200">{formatMoney(harvest.estimated_revenue)}</dd>
                                            </div>
                                        </dl>

                                        {harvest.notes && (
                                            <p className="mt-4 border-t border-white/5 pt-3 text-sm text-zinc-400">
                                                {harvest.notes}
                                            </p>
                                        )}
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
                        aria-labelledby="harvest-form-title"
                        className="my-4 w-full max-w-2xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="harvest-form-title" className="text-lg font-semibold">
                                    Catat panen
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    Rata-rata berat dan estimasi revenue dihitung otomatis oleh server.
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

                            <FormField id="harvest-cycle" label="Siklus budidaya" error={formErrors.fish_cycle_id}>
                                <select
                                    id="harvest-cycle"
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
                                            {cycle.pond ? ` · ${cycle.pond.code} · ${cycle.pond.name}` : ""}
                                            {` · ${STATUS_LABELS[cycle.status]} · populasi awal ${formatCount(cycle.initial_fish_count)}`}
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
                                <FormField id="harvest-date" label="Tanggal panen" error={formErrors.harvest_date}>
                                    <input
                                        id="harvest-date"
                                        type="date"
                                        value={form.harvest_date}
                                        onChange={(event) => setForm({ ...form, harvest_date: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.harvest_date)}
                                    />
                                </FormField>
                                <FormField id="harvest-total-fish" label="Total ikan" error={formErrors.total_fish}>
                                    <input
                                        id="harvest-total-fish"
                                        type="number"
                                        min="1"
                                        step="1"
                                        value={form.total_fish}
                                        onChange={(event) => setForm({ ...form, total_fish: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.total_fish)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="harvest-total-weight" label="Total berat (kg)" error={formErrors.total_weight}>
                                    <input
                                        id="harvest-total-weight"
                                        type="number"
                                        min="0.001"
                                        step="0.001"
                                        value={form.total_weight}
                                        onChange={(event) => setForm({ ...form, total_weight: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.total_weight)}
                                    />
                                </FormField>
                                <FormField id="harvest-selling-price" label="Harga jual per kg (Rp)" error={formErrors.selling_price_per_kg}>
                                    <input
                                        id="harvest-selling-price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.selling_price_per_kg}
                                        onChange={(event) => setForm({ ...form, selling_price_per_kg: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.selling_price_per_kg)}
                                    />
                                </FormField>
                            </div>

                            <FormField id="harvest-notes" label="Catatan (opsional)" error={formErrors.notes}>
                                <textarea
                                    id="harvest-notes"
                                    value={form.notes}
                                    onChange={(event) => setForm({ ...form, notes: event.target.value })}
                                    rows={3}
                                    className={`${inputClass(!!formErrors.notes)} resize-y`}
                                />
                            </FormField>

                            <div className="rounded-lg border border-orange-400/15 bg-orange-400/5 px-4 py-3 text-sm text-orange-100">
                                Rata-rata berat (g/ekor), estimasi revenue, dan perubahan status siklus dihitung oleh server.
                            </div>

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
                                    {saving ? "Menyimpan..." : "Simpan panen"}
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
