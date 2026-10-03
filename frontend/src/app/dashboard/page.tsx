"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api/client";

type DashboardResponse = {
    success: boolean;
    data: Record<string, unknown>;
};

export default function DashboardPage() {
    const router = useRouter();

    const [dashboard, setDashboard] = useState<DashboardResponse | null>(null);

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        async function loadDashboard() {
            const token = localStorage.getItem("auth_token");

            if (!token) {
                router.replace("/login");
                return;
            }

            try {
                const response = await api<DashboardResponse>("/dashboard");

                setDashboard(response);
            } catch (err) {
                const message =
                    err instanceof Error ? err.message : "API request failed";

                if (message.toLowerCase().includes("unauthenticated")) {
                    localStorage.removeItem("auth_token");
                    localStorage.removeItem("auth_user");

                    router.replace("/login");
                    return;
                }

                setError(message);
            } finally {
                setLoading(false);
            }
        }

        loadDashboard();
    }, [router]);

    if (loading) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-zinc-950">
                <p className="text-sm text-zinc-500">Loading dashboard...</p>
            </main>
        );
    }

    if (error) {
        return (
            <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-6">
                <div className="rounded-2xl border border-red-500/20 bg-red-500/5 p-6">
                    <p className="font-medium text-red-400">Dashboard Error</p>

                    <p className="mt-2 text-sm text-zinc-400">{error}</p>
                </div>
            </main>
        );
    }

    return (
        <main className="min-h-screen bg-zinc-950 text-white">
            <div className="mx-auto max-w-7xl px-6 py-8">
                <div className="mb-8">
                    <p className="text-sm font-medium text-emerald-400">
                        Lele Management
                    </p>

                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Dashboard
                    </h1>

                    <p className="mt-2 text-sm text-zinc-500">
                        Overview of your fish farming operation.
                    </p>

                    <nav className="mt-5 flex flex-wrap gap-2" aria-label="Manajemen budidaya">
                        <Link
                            href="/ponds"
                            className="inline-flex items-center rounded-lg border border-emerald-400/30 px-4 py-2 text-sm font-medium text-emerald-300 transition hover:border-emerald-300/60 hover:bg-emerald-400/10"
                        >
                            Kelola kolam
                        </Link>
                        <Link
                            href="/fish-cycles"
                            className="inline-flex items-center rounded-lg border border-sky-400/30 px-4 py-2 text-sm font-medium text-sky-200 transition hover:border-sky-300/60 hover:bg-sky-400/10"
                        >
                            Kelola siklus
                        </Link>
                        <Link
                            href="/feedings"
                            className="inline-flex items-center rounded-lg border border-amber-400/30 px-4 py-2 text-sm font-medium text-amber-200 transition hover:border-amber-300/60 hover:bg-amber-400/10"
                        >
                            Catat pakan
                        </Link>
                        <Link
                            href="/mortality"
                            className="inline-flex items-center rounded-lg border border-rose-400/30 px-4 py-2 text-sm font-medium text-rose-200 transition hover:border-rose-300/60 hover:bg-rose-400/10"
                        >
                            Catat mortalitas
                        </Link>
                        <Link
                            href="/samplings"
                            className="inline-flex items-center rounded-lg border border-cyan-400/30 px-4 py-2 text-sm font-medium text-cyan-200 transition hover:border-cyan-300/60 hover:bg-cyan-400/10"
                        >
                            Sampling
                        </Link>
                        <Link
                            href="/harvests"
                            className="inline-flex items-center rounded-lg border border-orange-400/30 px-4 py-2 text-sm font-medium text-orange-200 transition hover:border-orange-300/60 hover:bg-orange-400/10"
                        >
                            Panen
                        </Link>
                        <Link
                            href="/purchases"
                            className="inline-flex items-center rounded-lg border border-lime-400/30 px-4 py-2 text-sm font-medium text-lime-200 transition hover:border-lime-300/60 hover:bg-lime-400/10"
                        >
                            Pembelian
                        </Link>
                    </nav>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Ponds"
                        value="—"
                        description="Total active ponds"
                    />

                    <StatCard
                        title="Fish Cycles"
                        value="—"
                        description="Active cultivation cycles"
                    />

                    <StatCard
                        title="Feedings"
                        value="—"
                        description="Recorded feedings"
                    />

                    <StatCard
                        title="Cash Balance"
                        value="—"
                        description="Current cash balance"
                    />
                </div>

                <div className="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 className="text-lg font-semibold">API Connected</h2>

                    <p className="mt-2 text-sm text-zinc-500">
                        Dashboard successfully connected to Laravel API.
                    </p>

                    <pre className="mt-5 overflow-x-auto rounded-xl border border-white/10 bg-black/40 p-4 text-xs text-zinc-400">
                        {JSON.stringify(dashboard?.data, null, 2)}
                    </pre>
                </div>
            </div>
        </main>
    );
}

function StatCard({
    title,
    value,
    description,
}: {
    title: string;
    value: string;
    description: string;
}) {
    return (
        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-white/15 hover:bg-white/[0.05]">
            <p className="text-sm text-zinc-500">{title}</p>

            <p className="mt-3 text-2xl font-semibold">{value}</p>

            <p className="mt-1 text-xs text-zinc-600">{description}</p>
        </div>
    );
}
