"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ApiError, api } from "@/lib/api/client";

type CycleStatus = "planned" | "active" | "harvest" | "completed" | "cancelled";

type CycleOption = {
    id: number;
    code: string;
    status: CycleStatus;
};

type ProductOption = {
    id: number;
    code: string;
    name: string;
    unit: string;
    current_stock: string | number;
    is_active: boolean;
};

type Feeding = {
    id: number;
    fish_cycle: {
        id: number;
        code: string;
    };
    product: {
        id: number;
        code: string;
        name: string;
        unit: string;
    };
    feeding_date: string;
    feeding_time: string | null;
    quantity: number;
    unit_price: number;
    total_cost: number;
    method: string | null;
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

type FeedingForm = {
    fish_cycle_id: string;
    product_id: string;
    feeding_date: string;
    feeding_time: string;
    quantity: string;
    unit_price: string;
    notes: string;
};

function today(): string {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10);
}

function emptyForm(): FeedingForm {
    return {
        fish_cycle_id: "",
        product_id: "",
        feeding_date: today(),
        feeding_time: "",
        quantity: "",
        unit_price: "",
        notes: "",
    };
}

function displayDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split("-");

    return `${day}/${month}/${year}`;
}

function formatQuantity(value: number): string {
    return new Intl.NumberFormat("id-ID", {
        maximumFractionDigits: 3,
    }).format(value);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 2,
    }).format(value);
}

function validateForm(
    form: FeedingForm,
    products: ProductOption[],
    editing: Feeding | null,
): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.fish_cycle_id) {
        errors.fish_cycle_id = "Siklus budidaya wajib dipilih.";
    }

    if (!form.product_id) {
        errors.product_id = "Produk pakan wajib dipilih.";
    }

    if (!form.feeding_date) {
        errors.feeding_date = "Tanggal pemberian wajib diisi.";
    }

    if (form.feeding_time && !/^([01]\d|2[0-3]):[0-5]\d$/.test(form.feeding_time)) {
        errors.feeding_time = "Waktu harus menggunakan format jam dan menit.";
    }

    const quantity = Number(form.quantity);

    if (!form.quantity || !Number.isFinite(quantity) || quantity <= 0) {
        errors.quantity = "Jumlah pakan harus lebih besar dari 0.";
    } else if (!/^\d+(\.\d{1,3})?$/.test(form.quantity)) {
        errors.quantity = "Jumlah pakan maksimal tiga angka desimal.";
    }

    const unitPrice = Number(form.unit_price);

    if (form.unit_price === "" || !Number.isFinite(unitPrice) || unitPrice < 0) {
        errors.unit_price = "Harga satuan tidak boleh negatif.";
    } else if (!/^\d+(\.\d{1,2})?$/.test(form.unit_price)) {
        errors.unit_price = "Harga satuan maksimal dua angka desimal.";
    }

    const product = products.find((option) => option.id === Number(form.product_id));

    if (product && quantity > 0) {
        const editStockCredit = editing?.product.id === product.id
            ? editing.quantity
            : 0;
        const availableStock = Number(product.current_stock) + editStockCredit;

        if (quantity > availableStock) {
            errors.quantity = `Stok tersedia ${formatQuantity(availableStock)} ${product.unit}.`;
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

async function loadAllPages<T>(path: string): Promise<T[]> {
    const results: T[] = [];
    let currentPage = 1;
    let lastPage = 1;

    do {
        const response = await api<PaginatedResponse<T>>(`${path}?page=${currentPage}`);

        results.push(...response.data);
        lastPage = response.meta.last_page;
        currentPage += 1;
    } while (currentPage <= lastPage);

    return results;
}

export default function FeedingsPage() {
    const router = useRouter();
    const [feedings, setFeedings] = useState<Feeding[]>([]);
    const [cycles, setCycles] = useState<CycleOption[]>([]);
    const [products, setProducts] = useState<ProductOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [optionsLoading, setOptionsLoading] = useState(true);
    const [error, setError] = useState("");
    const [optionsError, setOptionsError] = useState("");
    const [success, setSuccess] = useState("");
    const [search, setSearch] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [editingFeeding, setEditingFeeding] = useState<Feeding | null>(null);
    const [form, setForm] = useState<FeedingForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    useEffect(() => {
        let active = true;

        async function loadFeedings() {
            if (!localStorage.getItem("auth_token")) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PaginatedResponse<Feeding>>(
                    `/feedings?page=${page}`,
                );

                if (!active) {
                    return;
                }

                setFeedings(response.data);
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

        void loadFeedings();

        return () => {
            active = false;
        };
    }, [page, reloadKey, router]);

    useEffect(() => {
        let active = true;

        async function loadOptions() {
            if (!localStorage.getItem("auth_token")) {
                return;
            }

            setOptionsLoading(true);
            setOptionsError("");

            try {
                const [allCycles, allProducts] = await Promise.all([
                    loadAllPages<CycleOption>("/fish-cycles"),
                    loadAllPages<ProductOption>("/products"),
                ]);

                if (active) {
                    setCycles(allCycles);
                    setProducts(allProducts);
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

                setOptionsError(getErrorMessage(requestError));
            } finally {
                if (active) {
                    setOptionsLoading(false);
                }
            }
        }

        void loadOptions();

        return () => {
            active = false;
        };
    }, [router]);

    const visibleFeedings = feedings.filter((feeding) => {
        const query = search.trim().toLowerCase();

        return !query || [
            feeding.fish_cycle.code,
            feeding.product.code,
            feeding.product.name,
            feeding.notes ?? "",
        ].some((value) => value.toLowerCase().includes(query));
    });

    const currentCycle = cycles.find(
        (cycle) => cycle.id === Number(form.fish_cycle_id),
    );
    const eligibleCycles = cycles.filter(
        (cycle) =>
            cycle.status === "active" ||
            cycle.status === "harvest" ||
            cycle.id === editingFeeding?.fish_cycle.id,
    );
    const costPreview =
        Number(form.quantity) > 0 && Number(form.unit_price) >= 0
            ? Number(form.quantity) * Number(form.unit_price)
            : null;

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
        setEditingFeeding(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
        setFormOpen(true);
    }

    function openEditForm(feeding: Feeding) {
        setEditingFeeding(feeding);
        setForm({
            fish_cycle_id: String(feeding.fish_cycle.id),
            product_id: String(feeding.product.id),
            feeding_date: feeding.feeding_date.slice(0, 10),
            feeding_time: feeding.feeding_time ?? "",
            quantity: String(feeding.quantity),
            unit_price: String(feeding.unit_price),
            notes: feeding.notes ?? "",
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
        setEditingFeeding(null);
        setForm(emptyForm());
        setFormErrors({});
        setFormError("");
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setFormError("");

        const clientErrors = validateForm(form, products, editingFeeding);

        if (Object.keys(clientErrors).length > 0) {
            setFormErrors(clientErrors);
            return;
        }

        setFormErrors({});
        setSaving(true);

        const payload = {
            fish_cycle_id: Number(form.fish_cycle_id),
            product_id: Number(form.product_id),
            feeding_date: form.feeding_date,
            feeding_time: form.feeding_time || null,
            quantity: Number(form.quantity),
            unit_price: Number(form.unit_price),
            notes: form.notes.trim() || null,
        };

        try {
            if (editingFeeding) {
                await api(`/feedings/${editingFeeding.id}`, {
                    method: "PUT",
                    body: JSON.stringify(payload),
                });
                setSuccess("Catatan pakan berhasil diperbarui.");
            } else {
                await api("/feedings", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                setSuccess("Catatan pakan berhasil dibuat.");
            }

            setFormOpen(false);
            setEditingFeeding(null);
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

    async function handleDelete(feeding: Feeding) {
        if (!window.confirm(`Hapus catatan pakan untuk ${feeding.product.name}? Stok akan dikembalikan.`)) {
            return;
        }

        setDeletingId(feeding.id);
        setError("");
        setSuccess("");

        try {
            await api(`/feedings/${feeding.id}`, { method: "DELETE" });
            setSuccess("Catatan pakan dihapus dan stok dikembalikan.");

            if (feedings.length === 1 && page > 1) {
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

    function refreshFeedings() {
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
                            Pemberian Pakan
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} catatan pakan
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshFeedings}
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
                            Catat pakan
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
                            onClick={refreshFeedings}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                <div className="mb-4 max-w-sm">
                    <label htmlFor="feeding-search" className="sr-only">
                        Cari catatan pakan
                    </label>
                    <input
                        id="feeding-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari siklus atau produk"
                        className="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-emerald-400/60"
                    />
                </div>

                {loading && feedings.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-36 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && visibleFeedings.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">
                            {search ? "Catatan tidak ditemukan" : "Belum ada catatan pakan"}
                        </h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            {search
                                ? "Coba kata kunci lain."
                                : "Catat pemberian pakan untuk siklus budidaya yang berjalan."}
                        </p>
                        {!search && (
                            <button
                                type="button"
                                onClick={openCreateForm}
                                className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                            >
                                Catat pakan
                            </button>
                        )}
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {visibleFeedings.map((feeding) => (
                                <article
                                    key={feeding.id}
                                    className="grid gap-4 rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-5"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-3">
                                            <span className="font-mono text-xs text-zinc-400">
                                                {feeding.fish_cycle.code}
                                            </span>
                                            <span className="text-xs text-zinc-500">
                                                {displayDate(feeding.feeding_date)}
                                                {feeding.feeding_time ? ` · ${feeding.feeding_time}` : ""}
                                            </span>
                                            {feeding.method && (
                                                <span className="rounded-full border border-white/10 px-2 py-1 text-xs text-zinc-400">
                                                    {feeding.method}
                                                </span>
                                            )}
                                        </div>
                                        <h2 className="mt-2 truncate text-lg font-semibold">
                                            {feeding.product.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-zinc-400">
                                            {feeding.product.code}
                                        </p>

                                        <dl className="mt-4 grid grid-cols-2 gap-x-5 gap-y-3 text-sm sm:grid-cols-4">
                                            <div>
                                                <dt className="text-xs text-zinc-500">Jumlah</dt>
                                                <dd className="mt-1 text-zinc-200">
                                                    {formatQuantity(feeding.quantity)} {feeding.product.unit}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Harga satuan</dt>
                                                <dd className="mt-1 text-zinc-200">{formatMoney(feeding.unit_price)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Total biaya</dt>
                                                <dd className="mt-1 font-medium text-amber-200">{formatMoney(feeding.total_cost)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-xs text-zinc-500">Waktu</dt>
                                                <dd className="mt-1 text-zinc-200">{feeding.feeding_time ?? "-"}</dd>
                                            </div>
                                        </dl>

                                        {feeding.notes && (
                                            <p className="mt-4 border-t border-white/5 pt-3 text-sm text-zinc-400">
                                                {feeding.notes}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex gap-2 border-t border-white/5 pt-3 sm:border-0 sm:pt-0">
                                        <button
                                            type="button"
                                            onClick={() => openEditForm(feeding)}
                                            className="rounded-lg border border-white/10 px-3 py-2 text-sm font-medium text-zinc-200 transition hover:border-white/20 hover:bg-white/5"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => void handleDelete(feeding)}
                                            disabled={deletingId === feeding.id}
                                            className="rounded-lg border border-red-400/20 px-3 py-2 text-sm font-medium text-red-200 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {deletingId === feeding.id ? "Menghapus..." : "Hapus"}
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
                        aria-labelledby="feeding-form-title"
                        className="my-4 w-full max-w-2xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="feeding-form-title" className="text-lg font-semibold">
                                    {editingFeeding ? "Ubah catatan pakan" : "Catat pakan"}
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    Pilih siklus berjalan dan produk dengan stok yang cukup.
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

                            {optionsError && (
                                <div
                                    role="alert"
                                    className="rounded-lg border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"
                                >
                                    Gagal memuat pilihan siklus/produk: {optionsError}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="feeding-cycle" label="Siklus budidaya" error={formErrors.fish_cycle_id}>
                                    <select
                                        id="feeding-cycle"
                                        value={form.fish_cycle_id}
                                        onChange={(event) => setForm({ ...form, fish_cycle_id: event.target.value })}
                                        required
                                        disabled={optionsLoading || eligibleCycles.length === 0}
                                        className={inputClass(!!formErrors.fish_cycle_id)}
                                    >
                                        <option value="">
                                            {optionsLoading ? "Memuat siklus..." : "Pilih siklus"}
                                        </option>
                                        {eligibleCycles.map((cycle) => (
                                            <option key={cycle.id} value={cycle.id}>
                                                {cycle.code} · {cycle.status === "active" ? "Berjalan" : "Panen"}
                                            </option>
                                        ))}
                                    </select>
                                    {!optionsLoading && eligibleCycles.length === 0 && !optionsError && (
                                        <p className="mt-1.5 text-xs text-amber-200">
                                            Tidak ada siklus berstatus berjalan atau panen.
                                        </p>
                                    )}
                                    {currentCycle && !["active", "harvest"].includes(currentCycle.status) && (
                                        <p className="mt-1.5 text-xs text-amber-200">
                                            Backend hanya menerima pakan pada siklus berjalan atau panen.
                                        </p>
                                    )}
                                </FormField>
                                <FormField id="feeding-product" label="Produk" error={formErrors.product_id}>
                                    <select
                                        id="feeding-product"
                                        value={form.product_id}
                                        onChange={(event) => setForm({ ...form, product_id: event.target.value })}
                                        required
                                        disabled={optionsLoading || products.length === 0}
                                        className={inputClass(!!formErrors.product_id)}
                                    >
                                        <option value="">
                                            {optionsLoading ? "Memuat produk..." : "Pilih produk"}
                                        </option>
                                        {products.map((product) => (
                                            <option key={product.id} value={product.id}>
                                                {product.code} · {product.name} · stok {formatQuantity(Number(product.current_stock))} {product.unit}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="feeding-date" label="Tanggal" error={formErrors.feeding_date}>
                                    <input
                                        id="feeding-date"
                                        type="date"
                                        value={form.feeding_date}
                                        onChange={(event) => setForm({ ...form, feeding_date: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.feeding_date)}
                                    />
                                </FormField>
                                <FormField id="feeding-time" label="Waktu (opsional)" error={formErrors.feeding_time}>
                                    <input
                                        id="feeding-time"
                                        type="time"
                                        value={form.feeding_time}
                                        onChange={(event) => setForm({ ...form, feeding_time: event.target.value })}
                                        className={inputClass(!!formErrors.feeding_time)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="feeding-quantity" label="Jumlah" error={formErrors.quantity}>
                                    <input
                                        id="feeding-quantity"
                                        type="number"
                                        min="0.001"
                                        step="0.001"
                                        value={form.quantity}
                                        onChange={(event) => setForm({ ...form, quantity: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.quantity)}
                                    />
                                </FormField>
                                <FormField id="feeding-unit-price" label="Harga satuan (Rp)" error={formErrors.unit_price}>
                                    <input
                                        id="feeding-unit-price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.unit_price}
                                        onChange={(event) => setForm({ ...form, unit_price: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.unit_price)}
                                    />
                                </FormField>
                            </div>

                            <div className="rounded-lg border border-white/10 bg-black/20 px-4 py-3">
                                <p className="text-xs text-zinc-500">Perkiraan total biaya</p>
                                <p className="mt-1 font-medium text-amber-200">
                                    {costPreview === null ? "-" : formatMoney(costPreview)}
                                </p>
                            </div>

                            <FormField id="feeding-notes" label="Catatan" error={formErrors.notes}>
                                <textarea
                                    id="feeding-notes"
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
                                    disabled={saving || optionsLoading || eligibleCycles.length === 0 || products.length === 0}
                                    className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving ? "Menyimpan..." : "Simpan pakan"}
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
