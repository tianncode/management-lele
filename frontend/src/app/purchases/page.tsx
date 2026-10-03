"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ApiError, api } from "@/lib/api/client";

type PaymentStatus = "unpaid" | "partial" | "paid";

type SupplierOption = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
};

type ProductOption = {
    id: number;
    code: string;
    name: string;
    unit: string;
    current_stock: string | number;
    average_price: string | number | null;
    is_active: boolean;
};

type PurchaseProduct = {
    id: number;
    code: string;
    name: string;
};

type PurchaseItem = {
    id: number;
    product: PurchaseProduct;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

type Purchase = {
    id: number;
    supplier: {
        id: number;
        name: string;
    } | null;
    invoice_number: string;
    purchase_date: string;
    subtotal: number;
    discount: number;
    additional_cost: number;
    total: number;
    payment_status: PaymentStatus;
    paid_amount: number;
    notes: string | null;
    items: PurchaseItem[];
};

type PaginatedResponse<T> = {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};

type PurchaseItemForm = {
    product_id: string;
    quantity: string;
    unit_price: string;
};

type PurchaseForm = {
    supplier_id: string;
    invoice_number: string;
    purchase_date: string;
    items: PurchaseItemForm[];
    discount: string;
    additional_cost: string;
    payment_status: PaymentStatus;
    paid_amount: string;
    notes: string;
};

const PAYMENT_STATUS_LABELS: Record<PaymentStatus, string> = {
    unpaid: "Belum dibayar",
    partial: "Dibayar sebagian",
    paid: "Lunas",
};

const PAYMENT_STATUS_STYLES: Record<PaymentStatus, string> = {
    unpaid: "border-amber-400/25 bg-amber-400/10 text-amber-200",
    partial: "border-sky-400/25 bg-sky-400/10 text-sky-200",
    paid: "border-emerald-400/25 bg-emerald-400/10 text-emerald-200",
};

function today(): string {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10);
}

function newItem(): PurchaseItemForm {
    return {
        product_id: "",
        quantity: "",
        unit_price: "",
    };
}

function emptyForm(): PurchaseForm {
    return {
        supplier_id: "",
        invoice_number: "",
        purchase_date: today(),
        items: [newItem()],
        discount: "0",
        additional_cost: "0",
        payment_status: "unpaid",
        paid_amount: "0",
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

function moneyValue(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

function purchaseTotals(form: PurchaseForm): {
    subtotal: number;
    discount: number;
    additionalCost: number;
    total: number;
} {
    const subtotal = moneyValue(
        form.items.reduce((sum, item) => {
            const quantity = Number(item.quantity) || 0;
            const unitPrice = Number(item.unit_price) || 0;

            return sum + quantity * unitPrice;
        }, 0),
    );
    const discount = Number(form.discount) || 0;
    const additionalCost = Number(form.additional_cost) || 0;

    return {
        subtotal,
        discount,
        additionalCost,
        total: moneyValue(subtotal - discount + additionalCost),
    };
}

function validateForm(form: PurchaseForm): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.supplier_id) {
        errors.supplier_id = "Supplier wajib dipilih.";
    }

    if (!form.invoice_number.trim()) {
        errors.invoice_number = "Nomor invoice wajib diisi.";
    } else if (form.invoice_number.trim().length > 100) {
        errors.invoice_number = "Nomor invoice maksimal 100 karakter.";
    }

    if (!form.purchase_date) {
        errors.purchase_date = "Tanggal pembelian wajib diisi.";
    }

    if (form.items.length === 0) {
        errors.items = "Tambahkan minimal satu item pembelian.";
    }

    form.items.forEach((item, index) => {
        if (!item.product_id) {
            errors[`items.${index}.product_id`] = "Produk wajib dipilih.";
        }

        const quantity = Number(item.quantity);

        if (!item.quantity || !Number.isFinite(quantity) || quantity <= 0) {
            errors[`items.${index}.quantity`] = "Jumlah harus lebih besar dari 0.";
        } else if (!/^\d+(\.\d{1,3})?$/.test(item.quantity)) {
            errors[`items.${index}.quantity`] = "Jumlah maksimal tiga angka desimal.";
        }

        const unitPrice = Number(item.unit_price);

        if (item.unit_price === "" || !Number.isFinite(unitPrice) || unitPrice < 0) {
            errors[`items.${index}.unit_price`] = "Harga satuan tidak boleh negatif.";
        } else if (!/^\d+(\.\d{1,2})?$/.test(item.unit_price)) {
            errors[`items.${index}.unit_price`] = "Harga satuan maksimal dua angka desimal.";
        }
    });

    for (const field of ["discount", "additional_cost"] as const) {
        const value = Number(form[field]);

        if (form[field] === "" || !Number.isFinite(value) || value < 0) {
            errors[field] = "Nilai tidak boleh negatif.";
        }
    }

    const { total } = purchaseTotals(form);

    if (total < 0) {
        errors.discount = "Discount tidak boleh membuat total pembelian negatif.";
    }

    const paidAmount = Number(form.paid_amount);

    if (
        form.paid_amount === "" ||
        !Number.isFinite(paidAmount) ||
        paidAmount < 0
    ) {
        errors.paid_amount = "Jumlah dibayar tidak boleh negatif.";
    } else if (paidAmount > total) {
        errors.paid_amount = "Jumlah dibayar tidak boleh melebihi total pembelian.";
    } else if (form.payment_status === "unpaid" && paidAmount !== 0) {
        errors.paid_amount = "Pembelian belum dibayar harus memiliki jumlah dibayar 0.";
    } else if (
        form.payment_status === "partial" &&
        (paidAmount <= 0 || paidAmount >= total)
    ) {
        errors.paid_amount = "Pembayaran sebagian harus lebih dari 0 dan kurang dari total.";
    } else if (form.payment_status === "paid" && paidAmount !== total) {
        errors.paid_amount = "Pembelian lunas harus dibayar sesuai total pembelian.";
    }

    return errors;
}

function fieldErrorsFrom(error: ApiError): Record<string, string> {
    return Object.fromEntries(
        Object.entries(error.errors ?? {}).map(([field, messages]) => [
            field,
            messages[0] ?? "Input tidak valid.",
        ]),
    );
}

function getErrorMessage(error: unknown): string {
    return error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat menghubungi API.";
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

export default function PurchasesPage() {
    const router = useRouter();
    const [purchases, setPurchases] = useState<Purchase[]>([]);
    const [products, setProducts] = useState<ProductOption[]>([]);
    const [suppliers, setSuppliers] = useState<SupplierOption[]>([]);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [reloadKey, setReloadKey] = useState(0);
    const [optionsReloadKey, setOptionsReloadKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const [optionsLoading, setOptionsLoading] = useState(true);
    const [error, setError] = useState("");
    const [optionsError, setOptionsError] = useState("");
    const [success, setSuccess] = useState("");
    const [search, setSearch] = useState("");
    const [formOpen, setFormOpen] = useState(false);
    const [form, setForm] = useState<PurchaseForm>(emptyForm);
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [formError, setFormError] = useState("");
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;

        async function loadPurchases() {
            if (!localStorage.getItem("auth_token")) {
                router.replace("/login");
                return;
            }

            setLoading(true);
            setError("");

            try {
                const response = await api<PaginatedResponse<Purchase>>(
                    `/purchases?page=${page}`,
                );

                if (!active) {
                    return;
                }

                setPurchases(response.data);
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

        void loadPurchases();

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
                const [allProducts, allSuppliers] = await Promise.all([
                    loadAllPages<ProductOption>("/products"),
                    loadAllPages<SupplierOption>("/suppliers"),
                ]);

                if (active) {
                    setProducts(allProducts);
                    setSuppliers(allSuppliers);
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
    }, [optionsReloadKey, router]);

    const visiblePurchases = purchases.filter((purchase) => {
        const query = search.trim().toLowerCase();

        return !query || [
            purchase.invoice_number,
            purchase.supplier?.name ?? "",
            purchase.notes ?? "",
            ...purchase.items.flatMap((item) => [item.product.code, item.product.name]),
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

    function updateForm(patch: Partial<PurchaseForm>) {
        setForm((current) => {
            const next = { ...current, ...patch };
            const totals = purchaseTotals(next);

            if (patch.payment_status === "unpaid") {
                next.paid_amount = "0";
            } else if (patch.payment_status === "paid") {
                next.paid_amount = totals.total.toFixed(2);
            } else if (patch.payment_status === "partial") {
                const paidAmount = Number(current.paid_amount);

                if (
                    paidAmount <= 0 ||
                    paidAmount >= totals.total
                ) {
                    next.paid_amount = totals.total >= 0.02
                        ? moneyValue(totals.total / 2).toFixed(2)
                        : "";
                }
            } else if (patch.discount !== undefined || patch.additional_cost !== undefined || patch.items !== undefined) {
                if (next.payment_status === "unpaid") {
                    next.paid_amount = "0";
                } else if (next.payment_status === "paid") {
                    next.paid_amount = totals.total.toFixed(2);
                }
            }

            return next;
        });
    }

    function updateItem(index: number, patch: Partial<PurchaseItemForm>) {
        const items = [...form.items];
        items[index] = { ...items[index], ...patch };
        updateForm({ items });
    }

    function chooseProduct(index: number, productId: string) {
        const product = products.find((item) => item.id === Number(productId));
        const row = form.items[index];

        updateItem(index, {
            product_id: productId,
            unit_price: row.unit_price || (product?.average_price == null
                ? ""
                : String(product.average_price)),
        });
    }

    function addItem() {
        updateForm({ items: [...form.items, newItem()] });
    }

    function removeItem(index: number) {
        if (form.items.length <= 1) {
            return;
        }

        updateForm({ items: form.items.filter((_, itemIndex) => itemIndex !== index) });
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
            supplier_id: Number(form.supplier_id),
            invoice_number: form.invoice_number.trim(),
            purchase_date: form.purchase_date,
            items: form.items.map((item) => ({
                product_id: Number(item.product_id),
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price),
            })),
            discount: Number(form.discount),
            additional_cost: Number(form.additional_cost),
            payment_status: form.payment_status,
            paid_amount: Number(form.paid_amount),
            notes: form.notes.trim() || null,
        };

        try {
            const purchase = await api<{
                data: Purchase;
            }>("/purchases", {
                method: "POST",
                body: JSON.stringify(payload),
            });

            setSuccess(`Pembelian ${purchase.data.invoice_number} berhasil dibuat.`);
            setFormOpen(false);
            setForm(emptyForm());
            setPage(1);
            setReloadKey((current) => current + 1);
            setOptionsReloadKey((current) => current + 1);
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

    function refreshPurchases() {
        setSuccess("");
        setReloadKey((current) => current + 1);
        setOptionsReloadKey((current) => current + 1);
    }

    const totals = purchaseTotals(form);

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
                            Pembelian
                        </h1>
                        <p className="mt-2 text-sm text-zinc-400">
                            {total} transaksi pembelian
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={refreshPurchases}
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
                            Tambah Pembelian
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
                            onClick={refreshPurchases}
                            className="shrink-0 font-medium underline underline-offset-4"
                        >
                            Coba lagi
                        </button>
                    </div>
                )}

                <div className="mb-4 max-w-sm">
                    <label htmlFor="purchase-search" className="sr-only">
                        Cari pembelian
                    </label>
                    <input
                        id="purchase-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari invoice, supplier, atau produk"
                        className="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-zinc-500 focus:border-emerald-400/60"
                    />
                </div>

                {loading && purchases.length === 0 ? (
                    <div className="space-y-3" aria-live="polite">
                        {[1, 2, 3].map((item) => (
                            <div
                                key={item}
                                className="h-36 animate-pulse rounded-lg border border-white/10 bg-white/[0.03]"
                            />
                        ))}
                    </div>
                ) : !loading && visiblePurchases.length === 0 && !error ? (
                    <section className="rounded-lg border border-dashed border-white/15 px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">
                            {search ? "Pembelian tidak ditemukan" : "Belum ada pembelian"}
                        </h2>
                        <p className="mt-2 text-sm text-zinc-400">
                            {search
                                ? "Coba kata kunci lain."
                                : "Catat penerimaan barang dan pembayaran supplier."}
                        </p>
                        {!search && (
                            <button
                                type="button"
                                onClick={openCreateForm}
                                className="mt-5 rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300"
                            >
                                Tambah Pembelian
                            </button>
                        )}
                    </section>
                ) : (
                    <>
                        <div className="grid gap-3">
                            {visiblePurchases.map((purchase) => (
                                <article
                                    key={purchase.id}
                                    className="rounded-lg border border-white/10 bg-white/[0.03] p-4 sm:p-5"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-3">
                                                <span className="font-mono text-sm text-zinc-200">
                                                    {purchase.invoice_number}
                                                </span>
                                                <span
                                                    className={`rounded-full border px-2.5 py-1 text-xs font-medium ${PAYMENT_STATUS_STYLES[purchase.payment_status]}`}
                                                >
                                                    {PAYMENT_STATUS_LABELS[purchase.payment_status]}
                                                </span>
                                            </div>
                                            <h2 className="mt-2 text-lg font-semibold">
                                                {purchase.supplier?.name ?? "Supplier tidak tersedia"}
                                            </h2>
                                            <p className="mt-1 text-sm text-zinc-400">
                                                {displayDate(purchase.purchase_date)} · {purchase.items.length} item
                                            </p>
                                        </div>
                                        <p className="text-lg font-semibold text-lime-200 sm:text-right">
                                            {formatMoney(purchase.total)}
                                        </p>
                                    </div>

                                    <div className="mt-4 flex flex-wrap gap-2 border-t border-white/5 pt-3">
                                        {purchase.items.map((item) => (
                                            <span
                                                key={item.id}
                                                className="rounded-md border border-white/10 bg-black/20 px-3 py-1.5 text-xs text-zinc-300"
                                            >
                                                {item.product.code} · {item.product.name} · {formatQuantity(item.quantity)} x {formatMoney(item.unit_price)}
                                            </span>
                                        ))}
                                    </div>

                                    <div className="mt-3 flex flex-wrap justify-between gap-2 border-t border-white/5 pt-3 text-xs text-zinc-500">
                                        <span>Dibayar: {formatMoney(purchase.paid_amount)}</span>
                                        <span>Subtotal {formatMoney(purchase.subtotal)} · Diskon {formatMoney(purchase.discount)} · Biaya tambahan {formatMoney(purchase.additional_cost)}</span>
                                    </div>

                                    {purchase.notes && (
                                        <p className="mt-3 text-sm text-zinc-400">{purchase.notes}</p>
                                    )}
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
                        aria-labelledby="purchase-form-title"
                        className="my-4 w-full max-w-4xl rounded-lg border border-white/10 bg-zinc-900 shadow-2xl"
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                            <div>
                                <h2 id="purchase-form-title" className="text-lg font-semibold">
                                    Tambah Pembelian
                                </h2>
                                <p className="mt-1 text-sm text-zinc-400">
                                    Stok bertambah dan kas keluar hanya sebesar pembayaran yang dicatat.
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
                                    Gagal memuat pilihan supplier/produk: {optionsError}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-3">
                                <FormField id="purchase-supplier" label="Supplier" error={formErrors.supplier_id}>
                                    <select
                                        id="purchase-supplier"
                                        value={form.supplier_id}
                                        onChange={(event) => updateForm({ supplier_id: event.target.value })}
                                        required
                                        disabled={optionsLoading || suppliers.length === 0}
                                        className={inputClass(!!formErrors.supplier_id)}
                                    >
                                        <option value="">
                                            {optionsLoading ? "Memuat supplier..." : "Pilih supplier"}
                                        </option>
                                        {suppliers.map((supplier) => (
                                            <option key={supplier.id} value={supplier.id}>
                                                {supplier.code} · {supplier.name}{supplier.is_active ? "" : " · Nonaktif"}
                                            </option>
                                        ))}
                                    </select>
                                    {!optionsLoading && suppliers.length === 0 && !optionsError && (
                                        <p className="mt-1.5 text-xs text-amber-200">
                                            Belum ada supplier.
                                        </p>
                                    )}
                                </FormField>
                                <FormField id="purchase-date" label="Tanggal pembelian" error={formErrors.purchase_date}>
                                    <input
                                        id="purchase-date"
                                        type="date"
                                        value={form.purchase_date}
                                        onChange={(event) => updateForm({ purchase_date: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.purchase_date)}
                                    />
                                </FormField>
                                <FormField id="purchase-invoice" label="Nomor invoice" error={formErrors.invoice_number}>
                                    <input
                                        id="purchase-invoice"
                                        value={form.invoice_number}
                                        onChange={(event) => updateForm({ invoice_number: event.target.value })}
                                        maxLength={100}
                                        required
                                        className={inputClass(!!formErrors.invoice_number)}
                                    />
                                </FormField>
                            </div>

                            <section className="space-y-3" aria-labelledby="purchase-items-title">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <h3 id="purchase-items-title" className="text-sm font-semibold text-zinc-200">
                                        Item pembelian
                                    </h3>
                                    <button
                                        type="button"
                                        onClick={addItem}
                                        className="rounded-lg border border-lime-400/25 px-3 py-2 text-sm font-medium text-lime-200 transition hover:bg-lime-400/10"
                                    >
                                        + Tambah item
                                    </button>
                                </div>
                                {formErrors.items && (
                                    <p role="alert" className="text-sm text-red-300">{formErrors.items}</p>
                                )}

                                {form.items.map((item, index) => {
                                    const selectedProduct = products.find(
                                        (product) => product.id === Number(item.product_id),
                                    );
                                    const itemSubtotal = moneyValue(
                                        (Number(item.quantity) || 0) * (Number(item.unit_price) || 0),
                                    );

                                    return (
                                        <div
                                            key={index}
                                            className="grid gap-3 rounded-lg border border-white/10 bg-black/20 p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end"
                                        >
                                            <FormField
                                                id={`purchase-product-${index}`}
                                                label="Produk"
                                                error={formErrors[`items.${index}.product_id`]}
                                            >
                                                <select
                                                    id={`purchase-product-${index}`}
                                                    value={item.product_id}
                                                    onChange={(event) => chooseProduct(index, event.target.value)}
                                                    required
                                                    disabled={optionsLoading || products.length === 0}
                                                    className={inputClass(!!formErrors[`items.${index}.product_id`])}
                                                >
                                                    <option value="">
                                                        {optionsLoading ? "Memuat produk..." : "Pilih produk"}
                                                    </option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.code} · {product.name} · stok {formatQuantity(Number(product.current_stock))} {product.unit}{product.is_active ? "" : " · Nonaktif"}
                                                        </option>
                                                    ))}
                                                </select>
                                                {selectedProduct && (
                                                    <p className="mt-1 text-xs text-zinc-500">
                                                        Stok sekarang {formatQuantity(Number(selectedProduct.current_stock))} {selectedProduct.unit}
                                                        {selectedProduct.average_price !== null && ` · Harga rata-rata ${formatMoney(Number(selectedProduct.average_price))}`}
                                                    </p>
                                                )}
                                            </FormField>
                                            <FormField
                                                id={`purchase-quantity-${index}`}
                                                label={`Jumlah${selectedProduct ? ` (${selectedProduct.unit})` : ""}`}
                                                error={formErrors[`items.${index}.quantity`]}
                                            >
                                                <input
                                                    id={`purchase-quantity-${index}`}
                                                    type="number"
                                                    min="0.001"
                                                    step="0.001"
                                                    value={item.quantity}
                                                    onChange={(event) => updateItem(index, { quantity: event.target.value })}
                                                    required
                                                    className={inputClass(!!formErrors[`items.${index}.quantity`])}
                                                />
                                            </FormField>
                                            <FormField
                                                id={`purchase-price-${index}`}
                                                label="Harga satuan (Rp)"
                                                error={formErrors[`items.${index}.unit_price`]}
                                            >
                                                <input
                                                    id={`purchase-price-${index}`}
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={item.unit_price}
                                                    onChange={(event) => updateItem(index, { unit_price: event.target.value })}
                                                    required
                                                    className={inputClass(!!formErrors[`items.${index}.unit_price`])}
                                                />
                                                <p className="mt-1 text-xs text-zinc-500">
                                                    Subtotal {formatMoney(itemSubtotal)}
                                                </p>
                                            </FormField>
                                            <button
                                                type="button"
                                                onClick={() => removeItem(index)}
                                                disabled={form.items.length <= 1}
                                                aria-label={`Hapus item ${index + 1}`}
                                                className="rounded-lg border border-red-400/20 px-3 py-2.5 text-sm text-red-200 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    );
                                })}
                            </section>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="purchase-discount" label="Discount (Rp)" error={formErrors.discount}>
                                    <input
                                        id="purchase-discount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.discount}
                                        onChange={(event) => updateForm({ discount: event.target.value })}
                                        className={inputClass(!!formErrors.discount)}
                                    />
                                </FormField>
                                <FormField id="purchase-additional-cost" label="Biaya tambahan (Rp)" error={formErrors.additional_cost}>
                                    <input
                                        id="purchase-additional-cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.additional_cost}
                                        onChange={(event) => updateForm({ additional_cost: event.target.value })}
                                        className={inputClass(!!formErrors.additional_cost)}
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 rounded-lg border border-white/10 bg-black/20 p-4 sm:grid-cols-3">
                                <SummaryValue label="Subtotal" value={formatMoney(totals.subtotal)} />
                                <SummaryValue label="Total" value={formatMoney(totals.total)} highlight />
                                <SummaryValue label="Estimasi dibayar" value={formatMoney(Number(form.paid_amount) || 0)} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField id="purchase-payment-status" label="Status pembayaran" error={formErrors.payment_status}>
                                    <select
                                        id="purchase-payment-status"
                                        value={form.payment_status}
                                        onChange={(event) => updateForm({ payment_status: event.target.value as PaymentStatus })}
                                        className={inputClass(!!formErrors.payment_status)}
                                    >
                                        <option value="unpaid">Belum dibayar</option>
                                        <option value="partial">Dibayar sebagian</option>
                                        <option value="paid">Lunas</option>
                                    </select>
                                </FormField>
                                <FormField id="purchase-paid-amount" label="Jumlah dibayar (Rp)" error={formErrors.paid_amount}>
                                    <input
                                        id="purchase-paid-amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.paid_amount}
                                        onChange={(event) => updateForm({ paid_amount: event.target.value })}
                                        required
                                        className={inputClass(!!formErrors.paid_amount)}
                                    />
                                </FormField>
                            </div>

                            <FormField id="purchase-notes" label="Catatan (opsional)" error={formErrors.notes}>
                                <textarea
                                    id="purchase-notes"
                                    value={form.notes}
                                    onChange={(event) => updateForm({ notes: event.target.value })}
                                    rows={3}
                                    className={`${inputClass(!!formErrors.notes)} resize-y`}
                                />
                            </FormField>

                            <div className="border-t border-white/10 pt-4 text-xs text-zinc-500">
                                Stok bertambah sesuai item. Kas keluar hanya sebesar jumlah dibayar, dan dicatat oleh server.
                            </div>

                            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
                                    disabled={saving || optionsLoading || products.length === 0 || suppliers.length === 0}
                                    className="rounded-lg bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {saving ? "Menyimpan..." : "Simpan pembelian"}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            )}
        </main>
    );
}

function SummaryValue({
    label,
    value,
    highlight = false,
}: {
    label: string;
    value: string;
    highlight?: boolean;
}) {
    return (
        <div>
            <p className="text-xs text-zinc-500">{label}</p>
            <p className={`mt-1 font-semibold ${highlight ? "text-lime-200" : "text-zinc-200"}`}>
                {value}
            </p>
        </div>
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
