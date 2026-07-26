---
name: nextjs-v15-app-patterns
description: Load skill ini saat membuat atau memperbaiki fitur Next.js 15 App Router: routing, server components, client components, API routes, middleware, data fetching, caching, atau optimisasi performa. Berisi pola Next.js 15 App Router (async params, opt-in caching, useActionState, React 19).
---

# Next.js 15 App Router Patterns — Company Standards

## Perbedaan Penting v15 vs v14

| Aspek | v15 (PAKAI ini) | v14 (JANGAN pakai) |
|-------|-----------------|---------------------|
| `params` | **Promise** (sync deprecated, masih jalan tapi akan dihapus di v16) | Object langsung (sync) |
| `searchParams` | **Promise** (sync deprecated) | Object langsung |
| fetch | **Tidak cached by default** | Cached by default |
| Form hook | `useActionState` dari `react` | `useFormState` dari `react-dom` |
| Pending state | `useActionState` return `isPending` langsung | `useFormStatus` (child component) |
| React | **19** | 18 |

## DO / DON'T — Pola Kritis v15

### params — Promise (sync deprecated, masih jalan)

```tsx
// ✅ DO: await params (v15 pattern)
export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
}

// ⚠️ DEPRECATED: sync masih jalan tapi akan dihapus di v16
export default async function Page({ params }: { params: { id: string } }) {
  const user = await getUser(params.id); // jalan tapi deprecated
}

// ❌ DON'T: jangan pakai use() di Server Component
const { id } = use(params); // use() hanya untuk Client Component
```

### Form — useActionState dari react (BUKAN useFormState)

```tsx
// ✅ DO: useActionState (v15 / React 19) — isPending langsung
import { useActionState } from "react";
const [state, formAction, isPending] = useActionState(action, initialState);

// ❌ DON'T: useFormState deprecated di React 19
import { useFormState } from "react-dom"; // DEPRECATED — pakai useActionState
```

### Fetch — TIDAK cached by default (berubah dari v14)

```tsx
// ✅ DO: eksplisit set cache jika perlu
const res = await fetch(url, { next: { revalidate: 60 } }); // cached 60s
const res = await fetch(url, { cache: "force-cache" });      // cached permanent

// ⚠️ HATI-HATI: fetch tanpa opsi = TIDAK cached (berbeda dari v14!)
const res = await fetch(url); // TIDAK cached di v15

// ❌ DON'T: jangan pakai "use cache" (itu v16)
"use cache"; // TIDAK ADA di v15
```

### Middleware — middleware.ts

```tsx
// ✅ DO: middleware.ts + export function middleware()
export function middleware(request: NextRequest) { ... }

// ❌ DON'T: proxy.ts tidak ada di v15
export function proxy(request: NextRequest) { ... } // TIDAK ADA — ini v16
```

## Struktur Direktori

```
app/           → App Router (routes, layouts, pages)
components/    → Reusable UI components
lib/           → Utilities, helpers, API clients
hooks/         → Custom React hooks
types/         → TypeScript type definitions
public/        → Static assets
tests/         → Test files
docker/        → Docker configs
```

## Perintah Penting

```bash
pnpm dev
pnpm build
pnpm start
pnpm lint
pnpm test
docker compose up -d
docker compose down
```

## Konvensi Penamaan File

- Component file: `PascalCase.tsx` (contoh: `UserProfile.tsx`)
- Utility/hook file: `camelCase.ts` (contoh: `useAuth.ts`, `formatDate.ts`)
- Route folder: `kebab-case` (contoh: `app/user-profile/page.tsx`)
- Custom hooks prefix: `use` (contoh: `useAuth`, `useFetchData`)
- Event handler prefix: `handle` (contoh: `handleClick`, `handleSubmit`)

## File Conventions (per route segment)

| File | Fungsi |
|---|---|
| `page.tsx` | UI unik untuk route tersebut |
| `layout.tsx` | Shared layout yang wrap children routes |
| `loading.tsx` | Loading UI (Suspense fallback) |
| `error.tsx` | Error boundary untuk route segment |
| `not-found.tsx` | UI untuk 404 |
| `route.ts` | API endpoint (di `app/api/`) |

## Keamanan Next.js

- XSS: hindari `dangerouslySetInnerHTML` — jika terpaksa, sanitize dengan `dompurify`
- Validasi input **WAJIB** dengan **Zod** di semua API route handler dan server action
- Jangan expose sensitive data di client-side — gunakan `server-only` package jika perlu
- `console.log` **DILARANG** di production — gunakan logging library
- Credentials/secrets di `.env.local`, hardcode **DILARANG**

## Environment Variables

- `NEXT_PUBLIC_*` — exposed ke client-side (browser), gunakan HANYA untuk data non-sensitif
- Tanpa prefix `NEXT_PUBLIC_` — hanya tersedia di server (Server Components, API routes, Server Actions)
- ❌ DILARANG taruh API keys, database URLs, atau secrets di `NEXT_PUBLIC_*`

```tsx
// ✅ Baik
const apiUrl = process.env.API_URL;                          // server-only
const appName = process.env.NEXT_PUBLIC_APP_NAME;            // client-safe

// ❌ DILARANG
const secret = process.env.NEXT_PUBLIC_API_SECRET;           // secret exposed ke browser!
```

## Optimasi

- Image: selalu gunakan `next/image` dengan width/height atau fill
- Link: selalu gunakan `next/link` untuk client-side navigation
- Font: gunakan `next/font` untuk self-hosted fonts
- Metadata: gunakan `generateMetadata` atau `metadata` export untuk SEO
- Dynamic imports: gunakan `next/dynamic` untuk lazy loading komponen berat
- Server-only: gunakan `server-only` package untuk mencegah import server code di client

## App Router Structure & Route Groups

```
app/
├── layout.tsx              → Root layout (html, body, providers)
├── page.tsx                → Home page
├── loading.tsx             → Global loading UI
├── error.tsx               → Global error boundary
├── not-found.tsx           → 404 page
├── (auth)/                 → Route group: TIDAK muncul di URL
│   ├── login/page.tsx      → /login (bukan /auth/login)
│   └── register/page.tsx   → /register (bukan /auth/register)
├── (dashboard)/            → Route group: layout berbeda tanpa ubah URL
│   ├── layout.tsx          → Dashboard layout (sidebar, nav)
│   ├── page.tsx
│   └── settings/page.tsx   → /settings
└── api/
    └── [...route]/route.ts → API handlers
```

Route groups `(nama)` digunakan untuk:
- **Organisasi**: kelompokkan route terkait tanpa mempengaruhi URL path
- **Layout berbeda**: setiap group bisa punya `layout.tsx` sendiri
- **Contoh**: `(auth)` punya layout minimal, `(dashboard)` punya layout dengan sidebar

## Server Components (Default)

```tsx
// app/users/page.tsx — Server Component (no "use client")
import { getUsers } from "@/lib/api/users";

export default async function UsersPage() {
  const users = await getUsers();

  return (
    <div>
      <h1>Users</h1>
      <UserList users={users} />
    </div>
  );
}

// app/users/[id]/page.tsx — Dynamic page (Next.js 15: params adalah Promise)
import { getUser } from "@/lib/api/users";

export default async function UserDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params; // Await di Next.js 15 (sync deprecated, dihapus di v16)
  const user = await getUser(id);

  return <UserProfile user={user} />;
}
```

## Client Components (Hanya Jika Perlu)

```tsx
// components/SearchInput.tsx
"use client";

import { useState, useEffect } from "react";
import { useDebounce } from "@/hooks/useDebounce";

interface SearchInputProps {
  onSearch: (query: string) => void;
  placeholder?: string;
}

export function SearchInput({ onSearch, placeholder = "Cari..." }: SearchInputProps) {
  const [query, setQuery] = useState("");
  const debouncedQuery = useDebounce(query, 300);

  useEffect(() => {
    onSearch(debouncedQuery);
  }, [debouncedQuery, onSearch]);

  return (
    <input
      type="text"
      value={query}
      onChange={(e) => setQuery(e.target.value)}
      placeholder={placeholder}
      className="w-full rounded-md border px-3 py-2"
    />
  );
}
```

## server-only Package

```tsx
// lib/api/users.ts — pastikan file ini TIDAK bisa di-import dari client component
import "server-only";

export async function getUsers() {
  const res = await fetch(`${process.env.API_URL}/users`); // API_URL = server-only env
  if (!res.ok) throw new Error("Failed to fetch users");
  return res.json();
}
```

Jika client component mencoba import file yang berisi `import "server-only"`, build akan **gagal** dengan error yang jelas — mencegah kebocoran secrets ke browser.

## Data Fetching & Caching (Next.js 15)

**Penting**: Di Next.js 15, `fetch` **TIDAK di-cache secara default** (berubah dari v14). Untuk mengaktifkan caching, gunakan opsi `next: { revalidate }`, `next: { tags }`, atau `cache: "force-cache"` secara eksplisit.

```tsx
// lib/api/users.ts
import "server-only";
import { cache } from "react";

export const getUsers = cache(async () => {
  const res = await fetch(`${process.env.API_URL}/users`, {
    next: { revalidate: 60 }, // Cache & revalidate setiap 60 detik
  });

  if (!res.ok) throw new Error("Failed to fetch users");
  return res.json();
});

export async function getUser(id: string) {
  const res = await fetch(`${process.env.API_URL}/users/${id}`, {
    next: { tags: [`user-${id}`] }, // Cache & on-demand revalidation
  });

  if (!res.ok) throw new Error("Failed to fetch user");
  return res.json();
}

// Tanpa opsi cache → TIDAK di-cache (default Next.js 15)
const res = await fetch(`${process.env.API_URL}/stats`);

// Force cache secara eksplisit
const res = await fetch(`${process.env.API_URL}/config`, {
  cache: "force-cache",
});
```

`React.cache()` digunakan untuk memoize function non-fetch (database queries, ORM calls) agar tidak dipanggil berulang dalam satu request.

## Server Actions + Zod Validation

```tsx
// app/users/actions.ts
"use server";

import { z } from "zod";
import { revalidateTag } from "next/cache";
import { redirect } from "next/navigation";

const createUserSchema = z.object({
  name: z.string().min(1, "Nama wajib diisi").max(100),
  email: z.string().email("Format email tidak valid"),
});

export type CreateUserState = {
  errors?: Record<string, string[]>;
  message?: string;
};

export async function createUser(
  prevState: CreateUserState,
  formData: FormData,
): Promise<CreateUserState> {
  const parsed = createUserSchema.safeParse({
    name: formData.get("name"),
    email: formData.get("email"),
  });

  if (!parsed.success) {
    return { errors: parsed.error.flatten().fieldErrors };
  }

  const res = await fetch(`${process.env.API_URL}/users`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(parsed.data),
  });

  if (!res.ok) {
    return { message: "Gagal membuat user. Silakan coba lagi." };
  }

  revalidateTag("users");
  redirect("/users");
}
```

## Form Handling (Server Action + Error Feedback)

```tsx
// components/CreateUserForm.tsx
"use client";

import { useActionState } from "react";
import { createUser, type CreateUserState } from "@/app/users/actions";

const initialState: CreateUserState = {};

export function CreateUserForm() {
  const [state, formAction, isPending] = useActionState(createUser, initialState);

  return (
    <form action={formAction}>
      <div>
        <label htmlFor="name">Nama</label>
        <input id="name" name="name" type="text" required />
        {state.errors?.name && (
          <p className="text-sm text-red-500">{state.errors.name[0]}</p>
        )}
      </div>

      <div>
        <label htmlFor="email">Email</label>
        <input id="email" name="email" type="email" required />
        {state.errors?.email && (
          <p className="text-sm text-red-500">{state.errors.email[0]}</p>
        )}
      </div>

      {state.message && (
        <p className="text-sm text-red-500">{state.message}</p>
      )}

      <button type="submit" disabled={isPending}>
        {isPending ? "Menyimpan..." : "Simpan"}
      </button>
    </form>
  );
}
```

## API Route Handlers + Zod Validation

```tsx
// app/api/users/route.ts
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

const createUserSchema = z.object({
  name: z.string().min(1).max(100),
  email: z.string().email(),
});

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const page = searchParams.get("page") ?? "1";

  // ... fetch data
  return NextResponse.json({ data: users, meta: { page } });
}

export async function POST(request: NextRequest) {
  const body = await request.json();

  const parsed = createUserSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json(
      { errors: parsed.error.flatten().fieldErrors },
      { status: 400 },
    );
  }

  // ... create with parsed.data (sudah tervalidasi)
  return NextResponse.json({ data: user }, { status: 201 });
}
```

### Error Handling di API Routes

Gunakan custom error classes untuk error non-validasi:

```tsx
// lib/errors.ts
export class AppError extends Error {
  constructor(
    message: string,
    public statusCode: number = 500,
    public code?: string,
  ) {
    super(message);
  }
}

export class NotFoundError extends AppError {
  constructor(resource: string) {
    super(`${resource} tidak ditemukan`, 404, "NOT_FOUND");
  }
}

export class UnauthorizedError extends AppError {
  constructor(message = "Akses tidak diizinkan") {
    super(message, 401, "UNAUTHORIZED");
  }
}

export class ConflictError extends AppError {
  constructor(message: string) {
    super(message, 409, "CONFLICT");
  }
}
```

```tsx
// lib/api-handler.ts — helper untuk handle errors di route handler
import { NextResponse } from "next/server";
import { AppError } from "@/lib/errors";

export function handleApiError(error: unknown) {
  if (error instanceof AppError) {
    return NextResponse.json(
      { error: { message: error.message, code: error.code } },
      { status: error.statusCode },
    );
  }

  console.error("Unexpected error:", error);
  return NextResponse.json(
    { error: { message: "Terjadi kesalahan server", code: "INTERNAL_ERROR" } },
    { status: 500 },
  );
}
```

```tsx
// Penggunaan di route handler
import { NotFoundError } from "@/lib/errors";
import { handleApiError } from "@/lib/api-handler";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const user = await getUser(id);
    if (!user) throw new NotFoundError("User");
    return NextResponse.json({ data: user });
  } catch (error) {
    return handleApiError(error);
  }
}
```

### Dynamic Route Handler (Next.js 15: params adalah Promise)

```tsx
// app/api/users/[id]/route.ts
import { NextRequest, NextResponse } from "next/server";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params; // Await di Next.js 15 (sync deprecated, dihapus di v16)

  // ... fetch user by id
  return NextResponse.json({ data: user });
}
```

**Catatan Next.js 15**: `params` di page, layout, dan route handler sekarang `Promise` — gunakan `await` (sync access masih jalan tapi deprecated, akan dihapus di v16).

## Middleware

```tsx
// middleware.ts (root project)
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const token = request.cookies.get("token")?.value;

  if (!token && request.nextUrl.pathname.startsWith("/dashboard")) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/dashboard/:path*", "/api/:path*"],
};
```

## Loading & Error States

```tsx
// app/users/loading.tsx
export default function Loading() {
  return <div className="animate-pulse">Loading...</div>;
}

// app/users/error.tsx
"use client";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="text-center">
      <h2>Terjadi kesalahan</h2>
      <button onClick={reset}>Coba lagi</button>
    </div>
  );
}
```

## Metadata & SEO

```tsx
// app/users/page.tsx — Static metadata
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Users",
  description: "Daftar semua user",
};

// app/users/[id]/page.tsx — Dynamic metadata
import type { Metadata } from "next";
import { getUser } from "@/lib/api/users";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ id: string }>;
}): Promise<Metadata> {
  const { id } = await params;
  const user = await getUser(id);

  return {
    title: user.name,
    description: `Profil ${user.name}`,
  };
}
```

## Font (next/font)

```tsx
// app/layout.tsx
import { Inter } from "next/font/google";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",     // prevent FOIT (Flash of Invisible Text)
  variable: "--font-inter",
});

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id" className={inter.variable}>
      <body>{children}</body>
    </html>
  );
}
```

## Dynamic Import (next/dynamic)

```tsx
// Lazy load komponen berat — hanya di-load saat dirender
import dynamic from "next/dynamic";

const HeavyChart = dynamic(() => import("@/components/HeavyChart"), {
  loading: () => <div className="animate-pulse h-64">Loading chart...</div>,
  ssr: false, // disable SSR jika komponen hanya client-side (contoh: chart library)
});

export default function DashboardPage() {
  return (
    <div>
      <h1>Dashboard</h1>
      <HeavyChart />
    </div>
  );
}
```

## Image & Link

- Selalu gunakan `next/image` untuk gambar — otomatis lazy load, responsive, dan optimisasi format
- Selalu gunakan `next/link` untuk navigasi internal — prefetch otomatis

```tsx
import Image from "next/image";
import Link from "next/link";

<Image src="/hero.jpg" alt="Hero" width={800} height={400} priority />
<Link href="/dashboard">Dashboard</Link>
```

## React Query untuk Client-Side State

```tsx
// hooks/useUsers.ts
"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";

export function useUsers() {
  return useQuery({
    queryKey: ["users"],
    queryFn: () => fetch("/api/users").then((res) => res.json()),
  });
}

export function useCreateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateUserInput) =>
      fetch("/api/users", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      }).then((res) => res.json()),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
    },
  });
}
```
