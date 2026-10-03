"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api/client";

type LoginResponse = {
    success: boolean;
    message: string;
    data: {
        user: {
            id: number;
            name: string;
            email: string;
        };
        token: string;
    };
};

export default function LoginPage() {
    const router = useRouter();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        setLoading(true);
        setError("");

        try {
            const response = await api<LoginResponse>("/login", {
                method: "POST",
                body: JSON.stringify({
                    email,
                    password,
                }),
            });

            localStorage.setItem("auth_token", response.data.token);
            localStorage.setItem(
                "auth_user",
                JSON.stringify(response.data.user),
            );

            router.push("/");
            router.refresh();
        } catch (err) {
            setError(err instanceof Error ? err.message : "Login gagal.");
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-6">
            <div className="w-full max-w-md">
                <div className="mb-8">
                    <p className="text-sm font-medium text-emerald-400">
                        Lele Management
                    </p>

                    <h1 className="mt-2 text-3xl font-semibold text-white">
                        Welcome back
                    </h1>

                    <p className="mt-2 text-sm text-zinc-500">
                        Sign in to manage your fish farming operation.
                    </p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-5 rounded-2xl border border-white/10 bg-white/[0.03] p-6"
                >
                    {error && (
                        <div className="rounded-xl border border-red-500/20 bg-red-500/5 px-4 py-3 text-sm text-red-400">
                            {error}
                        </div>
                    )}

                    <div>
                        <label
                            htmlFor="email"
                            className="mb-2 block text-sm text-zinc-300"
                        >
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            required
                            className="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-500/50"
                            placeholder="admin@example.com"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="mb-2 block text-sm text-zinc-300"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(event) =>
                                setPassword(event.target.value)
                            }
                            required
                            className="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-500/50"
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-medium text-black transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {loading ? "Signing in..." : "Sign in"}
                    </button>
                </form>
            </div>
        </main>
    );
}
