const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api";

type ApiOptions = RequestInit & {
    token?: string;
};

export type ApiValidationErrors = Record<string, string[]>;

export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly errors?: ApiValidationErrors,
    ) {
        super(message);
        this.name = "ApiError";
    }
}

export async function api<T>(
    endpoint: string,
    options: ApiOptions = {},
): Promise<T> {
    const { token, ...fetchOptions } = options;

    const headers = new Headers(fetchOptions.headers);

    headers.set("Accept", "application/json");

    if (fetchOptions.body && !headers.has("Content-Type")) {
        headers.set("Content-Type", "application/json");
    }

    const authToken =
        token ??
        (typeof window !== "undefined"
            ? localStorage.getItem("auth_token")
            : null);

    if (authToken) {
        headers.set("Authorization", `Bearer ${authToken}`);
    }

    const response = await fetch(`${API_URL}${endpoint}`, {
        ...fetchOptions,
        headers,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const errorData = data as {
            message?: string;
            errors?: ApiValidationErrors;
        } | null;

        throw new ApiError(
            response.status,
            errorData?.message ??
                `API request failed with status ${response.status}`,
            errorData?.errors,
        );
    }

    return data as T;
}
