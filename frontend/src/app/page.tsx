"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function Home() {
    const router = useRouter();

    useEffect(() => {
        const token = localStorage.getItem("auth_token");

        if (token) {
            router.replace("/dashboard");
        } else {
            router.replace("/login");
        }
    }, [router]);

    return (
        <main className="flex min-h-screen items-center justify-center bg-zinc-950">
            <p className="text-sm text-zinc-500">Loading...</p>
        </main>
    );
}
