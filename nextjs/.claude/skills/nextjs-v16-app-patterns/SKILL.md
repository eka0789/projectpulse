---
name: nextjs-v16-app-patterns
description: Load skill ini saat membuat atau memperbaiki fitur Next.js 16 App Router: routing, server components, client components, API routes, proxy, data fetching, caching ("use cache"), atau optimisasi performa. Berisi pola Next.js 16 App Router (async params enforced, "use cache" directive, proxy.ts, updateTag, React Compiler).
---

# Next.js 16 App Router Patterns — Company Standards

## Perbedaan Penting v16 vs v15

| Aspek | v16 (PAKAI ini) | v15 (JANGAN pakai) |
|-------|-----------------|---------------------|
| Async params | **Enforced** (sync dihapus total) | Async + sync masih jalan |
| Caching | `"use cache"` directive | `cache: "force-cache"` pada fetch |
| `revalidateTag` | `revalidateTag(tag, profile)` (1 arg deprecated) | `revalidateTag(tag)` |
| New: `updateTag` | `updateTag(tag)` (read-your-writes) | Tidak ada |
| Middleware | **`proxy.ts`** (rename dari middleware.ts) | `middleware.ts` |
| React Compiler | **Stable** (wajib `reactCompiler: true` di config) | Tidak ada |
| Parallel routes | `default.js` **WAJIB** di semua slots | Opsional |
| Bundler | Turbopack default | Turbopack opt-in |
| `next lint` | **Dihapus** — gunakan ESLint/Biome langsung | Tersedia |
| Node.js | **20.9+** (Node 18 dropped) | 18.17+ |

## DO / DON'T — Pola Kritis v16

### params — Async enforced (sync DIHAPUS total)

```tsx
// ✅ DO: await params (Server Component)
export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
}

// ✅ DO: use() untuk Client Component
"use client";
import { use } from "react";
const { id } = use(params);

// ❌ DON'T: sync access DIHAPUS — akan ERROR
export default async function Page({ params }: { params: { id: string } }) {
  const user = await getUser(params.id); // ERROR — params bukan object lagi
}
```

### Caching — "use cache" directive (BUKAN fetch options)

```tsx
// ✅ DO: "use cache" + cacheLife + cacheTag
async function getUsers() {
  "use cache";
  cacheLife("hours");
  cacheTag("users");
  return fetch(url).then(r => r.json());
}

// ✅ DO: tanpa "use cache" = tidak cached (default v16)
async function getStats() {
  return fetch(url).then(r => r.json()); // tidak cached
}

// ❌ DON'T: jangan pakai fetch cache options (pola v14/v15)
const res = await fetch(url, { cache: "force-cache" }); // JANGAN — pakai "use cache"
const res = await fetch(url, { next: { revalidate: 60 } }); // JANGAN — pakai cacheLife()
```

### Revalidation — revalidateTag + updateTag

```tsx
// ✅ DO: revalidateTag dengan 2 argumen (tag + profile)
revalidateTag("users", "max");

// ✅ DO: updateTag untuk read-your-writes setelah mutation
updateTag("users"); // UI langsung lihat data terbaru

// ⚠️ DEPRECATED: 1 argumen masih jalan tapi deprecated
revalidateTag("users"); // deprecated — tambahkan profile
```

### Proxy — proxy.ts (BUKAN middleware.ts)

```tsx
// ✅ DO: proxy.ts + export function proxy()
// proxy.ts (root project)
export function proxy(request: NextRequest) { ... }

// ❌ DON'T: middleware.ts deprecated di v16
// middleware.ts — JANGAN
export function middleware(request: NextRequest) { ... } // DEPRECATED
```

### React Compiler — WAJIB config, JANGAN manual memo

```tsx
// ✅ DO: aktifkan di next.config.ts, hapus useMemo/useCallback
// next.config.ts: { reactCompiler: true }
export function MyComponent({ items }) {
  const filtered = items.filter(i => i.active); // compiler auto-memo
}

// ❌ DON'T: jangan manual memo jika React Compiler aktif
const filtered = useMemo(() => items.filter(i => i.active), [items]); // TIDAK PERLU
const handler = useCallback(() => { ... }, [deps]); // TIDAK PERLU
```

### Parallel Routes — default.tsx WAJIB

```tsx
// ✅ DO: semua slot @nama WAJIB punya default.tsx
// app/(dashboard)/@sidebar/default.tsx
export default function Default() { return null; }

// ❌ DON'T: parallel route tanpa default = ERROR di v16
// app/(dashboard)/@sidebar/page.tsx TANPA default.tsx → ERROR
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
pnpm dev              # Turbopack default di v16
pnpm build
pnpm start
pnpm lint             # ESLint/Biome langsung (next lint dihapus di v16)
pnpm test
npx next typegen      # Generate route param types
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
| `default.tsx` | Fallback untuk parallel routes (**WAJIB** di v16) |
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

- Image: selalu gunakan `next/image` dengan width/height atau fill (`images.remotePatterns` wajib, `domains` deprecated)
- Link: selalu gunakan `next/link` untuk client-side navigation
- Font: gunakan `next/font` untuk self-hosted fonts
- Metadata: gunakan `generateMetadata` atau `metadata` export untuk SEO
- Dynamic imports: gunakan `next/dynamic` untuk lazy loading komponen berat
- Server-only: gunakan `server-only` package untuk mencegah import server code di client
- React Compiler: **jangan manual `useMemo`/`useCallback`** — compiler handle otomatis (wajib aktifkan `reactCompiler: true` di `next.config.ts`)

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
│   ├── @sidebar/           → Parallel route slot
│   │   ├── page.tsx
│   │   └── default.tsx     → WAJIB di v16!
│   └── settings/page.tsx   → /settings
└── api/
    └── [...route]/route.ts → API handlers
```

Route groups `(nama)` digunakan untuk:
- **Organisasi**: kelompokkan route terkait tanpa mempengaruhi URL path
- **Layout berbeda**: setiap group bisa punya `layout.tsx` sendiri
- **Contoh**: `(auth)` punya layout minimal, `(dashboard)` punya layout dengan sidebar

**Parallel Routes**: Di v16, semua slot `@nama` **WAJIB** punya `default.tsx` sebagai fallback.

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

// app/users/[id]/page.tsx — Dynamic page (v16: params WAJIB await, sync dihapus total)
import { getUser } from "@/lib/api/users";

export default async function UserDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params; // WAJIB await — sync access DIHAPUS di v16

  const user = await getUser(id);
  return <UserProfile user={user} />;
}
```

### Client Component dengan params (gunakan `use()`)

```tsx
// components/UserDetail.tsx
"use client";

import { use } from "react";

export default function UserDetail({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params); // Client component: gunakan use() dari React

  return <div>User ID: {id}</div>;
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

// React Compiler di v16: TIDAK perlu useCallback/useMemo manual
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

## Data Fetching & Caching — "use cache" (Next.js 16)

**Penting**: Di Next.js 16, caching sepenuhnya **opt-in** via `"use cache"` directive. Fetch **TIDAK di-cache secara default**.

Aktifkan di `next.config.ts`:

```ts
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  cacheComponents: true,              // Aktifkan "use cache" directive
  reactCompiler: true,                // Aktifkan React Compiler (WAJIB untuk auto-memo)
};

export default nextConfig;
```

### Cache di level function

```tsx
// lib/api/users.ts
import "server-only";
import { cacheLife, cacheTag } from "next/cache";

export async function getUsers() {
  "use cache";
  cacheLife("hours");       // Built-in profiles: "default", "seconds", "hours", "days", "weeks", "max"
  cacheTag("users");        // Tag untuk revalidation

  const res = await fetch(`${process.env.API_URL}/users`);
  if (!res.ok) throw new Error("Failed to fetch users");
  return res.json();
}

export async function getUser(id: string) {
  "use cache";
  cacheLife("hours");
  cacheTag(`user-${id}`);

  const res = await fetch(`${process.env.API_URL}/users/${id}`);
  if (!res.ok) throw new Error("Failed to fetch user");
  return res.json();
}
```

### Cache di level page/component

```tsx
// app/products/page.tsx — seluruh page di-cache
export default async function ProductsPage() {
  "use cache";
  cacheLife("days");

  const products = await getProducts();
  return <ProductList items={products} />;
}
```

### Private cache — EXPERIMENTAL (akses cookies/headers dalam cache scope)

```tsx
export async function getUserProfile() {
  "use cache: private";     // EXPERIMENTAL — boleh akses cookies() dan headers()
  cacheLife("hours");

  const cookieStore = await cookies();
  const token = cookieStore.get("token")?.value;

  const res = await fetch(`${process.env.API_URL}/profile`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  return res.json();
}
```

### Tanpa cache (default behavior)

```tsx
// Tanpa "use cache" → TIDAK di-cache (default v16)
export async function getStats() {
  const res = await fetch(`${process.env.API_URL}/stats`);
  return res.json();
}
```

## Revalidation — revalidateTag & updateTag

```tsx
// app/users/actions.ts
"use server";

import { revalidateTag, updateTag } from "next/cache";

// revalidateTag: revalidate in background (stale-while-revalidate)
// v16: 2 argumen direkomendasikan (tag + profile). 1 argumen masih jalan tapi deprecated.
export async function refreshUsers() {
  revalidateTag("users", "max");
}

// updateTag: read-your-writes (tunggu data fresh sebelum respond)
// Gunakan setelah mutation agar UI langsung reflect perubahan
export async function createUser(formData: FormData) {
  // ... create user di API
  updateTag("users");   // UI langsung lihat data terbaru
}
```

## Server Actions + Zod Validation

```tsx
// app/users/actions.ts
"use server";

import { z } from "zod";
import { updateTag } from "next/cache";
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

  updateTag("users");   // v16: updateTag untuk read-your-writes
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
// lib/api-handler.ts
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

### Dynamic Route Handler (v16: params WAJIB await)

```tsx
// app/api/users/[id]/route.ts
import { NextRequest, NextResponse } from "next/server";
import { NotFoundError } from "@/lib/errors";
import { handleApiError } from "@/lib/api-handler";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params; // WAJIB await di v16
    const user = await getUser(id);
    if (!user) throw new NotFoundError("User");
    return NextResponse.json({ data: user });
  } catch (error) {
    return handleApiError(error);
  }
}
```

## Proxy (pengganti Middleware di v16)

```tsx
// proxy.ts (root project — BUKAN middleware.ts)
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

// v16: function name "proxy" (bukan "middleware")
export function proxy(request: NextRequest) {
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

**Catatan v16**: `middleware.ts` deprecated, rename ke `proxy.ts`. Function export berubah dari `middleware` ke `proxy` (named export `export function proxy()` atau default export `export default function proxy()` — keduanya valid). Proxy berjalan di **Node.js runtime** (bukan Edge Runtime).

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

// app/(dashboard)/@sidebar/default.tsx — WAJIB di v16 untuk parallel routes
export default function Default() {
  return null;
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

// app/users/[id]/page.tsx — Dynamic metadata (v16: params WAJIB await)
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

### Route Param Types (next typegen)

```tsx
// Setelah menjalankan `npx next typegen`, type helper tersedia global:
export default async function Page(props: PageProps<"/blog/[slug]">) {
  const { slug } = await props.params;
  return <article>{slug}</article>;
}
```

## next.config.ts (v16)

```ts
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  cacheComponents: true,              // Aktifkan "use cache" directive
  reactCompiler: true,                // Aktifkan React Compiler (WAJIB untuk auto-memo)
  images: {
    remotePatterns: [                  // WAJIB (images.domains deprecated)
      { protocol: "https", hostname: "example.com" },
    ],
  },
  // skipProxyUrlNormalize: true,      // Rename dari skipMiddlewareUrlNormalize
};

export default nextConfig;
```

## Font (next/font)

```tsx
// app/layout.tsx
import { Inter } from "next/font/google";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
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
import dynamic from "next/dynamic";

const HeavyChart = dynamic(() => import("@/components/HeavyChart"), {
  loading: () => <div className="animate-pulse h-64">Loading chart...</div>,
  ssr: false,
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

// React Compiler: TIDAK perlu useCallback manual
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

## Yang TIDAK Ada Lagi di v16

| Fitur | Status | Pengganti |
|-------|--------|-----------|
| `middleware.ts` | Deprecated | `proxy.ts` |
| `next lint` | Dihapus | ESLint/Biome langsung |
| Sync `params` | Dihapus | `await params` (async enforced) |
| `serverRuntimeConfig` | Dihapus | `.env` files |
| `publicRuntimeConfig` | Dihapus | `NEXT_PUBLIC_*` env vars |
| AMP support | Dihapus total | — |
| `experimental.ppr` | Dihapus | `cacheComponents` config |
| `images.domains` | Deprecated | `images.remotePatterns` |
