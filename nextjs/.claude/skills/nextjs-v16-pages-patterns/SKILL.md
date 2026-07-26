---
name: nextjs-v16-pages-patterns
description: Load skill ini saat membuat atau memperbaiki fitur Next.js 16 Pages Router: routing, data fetching (getServerSideProps/getStaticProps), API routes, proxy, atau konfigurasi project. Berisi pola Next.js 16 Pages Router (React 19 + React Compiler, proxy.ts).
---

# Next.js 16 Pages Router Patterns — Company Standards

## Catatan v16 untuk Pages Router

Pages Router di v16 **tidak mendapat fitur baru** (maintenance mode). Perubahan yang mempengaruhi Pages Router:

- **`proxy.ts`** menggantikan `middleware.ts` (lihat section Middleware/Proxy)
- **`next lint` dihapus** — gunakan ESLint/Biome langsung
- **React Compiler** stable — `useMemo`/`useCallback` manual tidak perlu lagi (wajib aktifkan `reactCompiler: true` di `next.config.ts`)
- **Node.js 20.9+** minimum (Node 18 dropped)
- **`images.remotePatterns`** wajib (menggantikan `images.domains`)

## DO / DON'T — Pola Kritis Pages Router v16

### Proxy — proxy.ts (BUKAN middleware.ts)

```tsx
// ✅ DO: proxy.ts + export function proxy()
// proxy.ts (root project)
export function proxy(request: NextRequest) { ... }

// ❌ DON'T: middleware.ts deprecated di v16
// middleware.ts — JANGAN
export function middleware(request: NextRequest) { ... } // DEPRECATED
```

### Lint — ESLint/Biome langsung (BUKAN next lint)

```bash
# ✅ DO: jalankan ESLint/Biome langsung
pnpm eslint .
pnpm biome check .

# ❌ DON'T: next lint dihapus di v16
pnpm next lint  # DIHAPUS — command tidak ada lagi
```

### React Compiler — WAJIB config, JANGAN manual memo

```tsx
// ✅ DO: aktifkan di next.config.ts, hapus useMemo/useCallback
// next.config.ts: { reactCompiler: true }
const filtered = items.filter(i => i.active); // compiler auto-memo

// ❌ DON'T: jangan manual memo jika React Compiler aktif
const filtered = useMemo(() => items.filter(i => i.active), [items]); // TIDAK PERLU
```

### Image Config — remotePatterns (BUKAN domains)

```ts
// ✅ DO: images.remotePatterns
images: { remotePatterns: [{ protocol: "https", hostname: "example.com" }] }

// ❌ DON'T: images.domains deprecated
images: { domains: ["example.com"] } // DEPRECATED
```

### Router — next/router (BUKAN next/navigation)

```tsx
// ✅ DO: useRouter dari next/router (Pages Router)
import { useRouter } from "next/router";

// ❌ DON'T: next/navigation hanya untuk App Router
import { useRouter } from "next/navigation"; // SALAH — ini App Router
```

## Struktur Direktori

```
pages/           → File-based routing
  _app.tsx       → Global layout wrapper
  _document.tsx  → Custom HTML document
  api/           → API route handlers
  404.tsx        → Custom 404 page
  500.tsx        → Custom 500 page
components/      → Reusable UI components
lib/             → Utilities, helpers, API clients
hooks/           → Custom React hooks
types/           → TypeScript type definitions
public/          → Static assets
styles/          → Global CSS
tests/           → Test files
docker/          → Docker configs
```

## Perintah Penting

```bash
pnpm dev
pnpm build
pnpm start
pnpm lint             # ESLint/Biome langsung (next lint dihapus di v16)
pnpm test
docker compose up -d
docker compose down
```

## Konvensi Penamaan File

- Component file: `PascalCase.tsx` (contoh: `UserProfile.tsx`)
- Utility/hook file: `camelCase.ts` (contoh: `useAuth.ts`, `formatDate.ts`)
- Page file: `kebab-case.tsx` atau `[param].tsx` (contoh: `pages/user-profile.tsx`)
- Custom hooks prefix: `use` (contoh: `useAuth`, `useFetchData`)
- Event handler prefix: `handle` (contoh: `handleClick`, `handleSubmit`)

## File Conventions

| File | Fungsi |
|---|---|
| `pages/_app.tsx` | Global layout, providers, shared state |
| `pages/_document.tsx` | Custom `<html>`, `<head>`, `<body>` |
| `pages/index.tsx` | Home page (`/`) |
| `pages/about.tsx` | Static route (`/about`) |
| `pages/users/[id].tsx` | Dynamic route (`/users/123`) |
| `pages/users/[...slug].tsx` | Catch-all route (`/users/a/b/c`) |
| `pages/api/users.ts` | API endpoint (`/api/users`) |
| `pages/404.tsx` | Custom 404 page |
| `pages/500.tsx` | Custom 500 page |

## Keamanan Next.js

- XSS: hindari `dangerouslySetInnerHTML` — jika terpaksa, sanitize dengan `dompurify`
- Validasi input **WAJIB** dengan **Zod** di semua API route handler
- Jangan expose sensitive data di client-side
- `console.log` **DILARANG** di production — gunakan logging library
- Credentials/secrets di `.env.local`, hardcode **DILARANG**

## Environment Variables

- `NEXT_PUBLIC_*` — exposed ke client-side (browser), gunakan HANYA untuk data non-sensitif
- Tanpa prefix `NEXT_PUBLIC_` — hanya tersedia di server (getServerSideProps, getStaticProps, API routes)
- DILARANG taruh API keys, database URLs, atau secrets di `NEXT_PUBLIC_*`

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
- Font: gunakan `next/font` untuk self-hosted fonts (v13+)
- Metadata: gunakan `next/head` untuk SEO per halaman
- Dynamic imports: gunakan `next/dynamic` untuk lazy loading komponen berat

## _app.tsx — Global Layout

```tsx
// pages/_app.tsx
import type { AppProps } from "next/app";
import "@/styles/globals.css";

export default function App({ Component, pageProps }: AppProps) {
  return (
    <div>
      <Component {...pageProps} />
    </div>
  );
}
```

### Dengan Providers

```tsx
// pages/_app.tsx
import type { AppProps } from "next/app";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import "@/styles/globals.css";

const queryClient = new QueryClient();

export default function App({ Component, pageProps }: AppProps) {
  return (
    <QueryClientProvider client={queryClient}>
      <Component {...pageProps} />
    </QueryClientProvider>
  );
}
```

### Per-Page Layout

```tsx
// pages/_app.tsx
import type { AppProps } from "next/app";
import type { NextPage } from "next";
import type { ReactElement, ReactNode } from "react";

type NextPageWithLayout = NextPage & {
  getLayout?: (page: ReactElement) => ReactNode;
};

type AppPropsWithLayout = AppProps & {
  Component: NextPageWithLayout;
};

export default function App({ Component, pageProps }: AppPropsWithLayout) {
  const getLayout = Component.getLayout ?? ((page) => page);
  return getLayout(<Component {...pageProps} />);
}

// pages/dashboard.tsx — halaman dengan layout khusus
import DashboardLayout from "@/components/layouts/DashboardLayout";

export default function DashboardPage() {
  return <div>Dashboard content</div>;
}

DashboardPage.getLayout = function getLayout(page: ReactElement) {
  return <DashboardLayout>{page}</DashboardLayout>;
};
```

## _document.tsx — Custom HTML Document

```tsx
// pages/_document.tsx
import { Html, Head, Main, NextScript } from "next/document";

export default function Document() {
  return (
    <Html lang="id">
      <Head />
      <body>
        <Main />
        <NextScript />
      </body>
    </Html>
  );
}
```

## Data Fetching — getServerSideProps (SSR)

Data di-fetch setiap request di server. Gunakan untuk data yang sering berubah.

```tsx
// pages/users/index.tsx
import type { InferGetServerSidePropsType, GetServerSideProps } from "next";

type User = {
  id: string;
  name: string;
  email: string;
};

export const getServerSideProps: GetServerSideProps<{
  users: User[];
}> = async (context) => {
  const res = await fetch(`${process.env.API_URL}/users`);

  if (!res.ok) {
    return { notFound: true };
  }

  const users: User[] = await res.json();
  return { props: { users } };
};

export default function UsersPage({
  users,
}: InferGetServerSidePropsType<typeof getServerSideProps>) {
  return (
    <div>
      <h1>Users</h1>
      <ul>
        {users.map((user) => (
          <li key={user.id}>{user.name}</li>
        ))}
      </ul>
    </div>
  );
}
```

### Dengan Redirect dan Auth

```tsx
export const getServerSideProps: GetServerSideProps = async (context) => {
  const token = context.req.cookies.token;

  if (!token) {
    return {
      redirect: {
        destination: "/login",
        permanent: false,
      },
    };
  }

  const res = await fetch(`${process.env.API_URL}/users`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!res.ok) return { notFound: true };

  return { props: { users: await res.json() } };
};
```

## Data Fetching — getStaticProps (SSG)

Data di-fetch saat build time. Gunakan untuk data yang jarang berubah.

```tsx
// pages/products/index.tsx
import type { InferGetStaticPropsType, GetStaticProps } from "next";

type Product = {
  id: string;
  name: string;
  price: number;
};

export const getStaticProps: GetStaticProps<{
  products: Product[];
}> = async () => {
  const res = await fetch(`${process.env.API_URL}/products`);
  const products: Product[] = await res.json();

  return {
    props: { products },
    revalidate: 60, // ISR: regenerate setiap 60 detik
  };
};

export default function ProductsPage({
  products,
}: InferGetStaticPropsType<typeof getStaticProps>) {
  return (
    <div>
      <h1>Products</h1>
      {products.map((product) => (
        <div key={product.id}>{product.name} — Rp{product.price}</div>
      ))}
    </div>
  );
}
```

## Data Fetching — getStaticPaths (Dynamic SSG)

Untuk halaman dinamis yang di-generate saat build time.

```tsx
// pages/products/[id].tsx
import type { GetStaticPaths, GetStaticProps, InferGetStaticPropsType } from "next";

export const getStaticPaths: GetStaticPaths = async () => {
  const res = await fetch(`${process.env.API_URL}/products`);
  const products = await res.json();

  const paths = products.map((product: { id: string }) => ({
    params: { id: product.id },
  }));

  return {
    paths,
    fallback: "blocking", // generate on-demand jika belum ada
    // fallback: false — return 404 untuk path yang tidak ada
    // fallback: true — render loading state dulu, lalu generate
  };
};

export const getStaticProps: GetStaticProps = async ({ params }) => {
  const res = await fetch(`${process.env.API_URL}/products/${params?.id}`);

  if (!res.ok) return { notFound: true };

  return {
    props: { product: await res.json() },
    revalidate: 60,
  };
};

export default function ProductPage({
  product,
}: InferGetStaticPropsType<typeof getStaticProps>) {
  return (
    <div>
      <h1>{product.name}</h1>
      <p>Rp{product.price}</p>
    </div>
  );
}
```

## API Route Handlers + Zod Validation

```tsx
// pages/api/users/index.ts
import type { NextApiRequest, NextApiResponse } from "next";
import { z } from "zod";

const createUserSchema = z.object({
  name: z.string().min(1).max(100),
  email: z.string().email(),
});

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse,
) {
  if (req.method === "GET") {
    const page = req.query.page ?? "1";
    // ... fetch data
    return res.status(200).json({ data: users, meta: { page } });
  }

  if (req.method === "POST") {
    const parsed = createUserSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ errors: parsed.error.flatten().fieldErrors });
    }
    // ... create with parsed.data
    return res.status(201).json({ data: user });
  }

  res.setHeader("Allow", ["GET", "POST"]);
  return res.status(405).json({ error: "Method not allowed" });
}
```

### Dynamic API Route

```tsx
// pages/api/users/[id].ts
import type { NextApiRequest, NextApiResponse } from "next";

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse,
) {
  const { id } = req.query;

  if (req.method === "GET") {
    const user = await getUser(id as string);
    if (!user) return res.status(404).json({ error: "User not found" });
    return res.status(200).json({ data: user });
  }

  if (req.method === "PUT") {
    // ... update user
    return res.status(200).json({ data: updatedUser });
  }

  if (req.method === "DELETE") {
    // ... delete user
    return res.status(204).end();
  }

  res.setHeader("Allow", ["GET", "PUT", "DELETE"]);
  return res.status(405).json({ error: "Method not allowed" });
}
```

## Routing — useRouter

```tsx
// Pages Router menggunakan next/router (BUKAN next/navigation)
import { useRouter } from "next/router";

export default function UserPage() {
  const router = useRouter();
  const { id } = router.query; // params langsung dari query

  const handleBack = () => {
    router.back();
  };

  const handleNavigate = () => {
    router.push("/users");
  };

  const handleReplace = () => {
    router.replace("/login");
  };

  return (
    <div>
      <p>User ID: {id}</p>
      <button onClick={handleBack}>Back</button>
      <button onClick={handleNavigate}>Go to Users</button>
    </div>
  );
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

**Catatan v16**: `middleware.ts` deprecated, rename ke `proxy.ts`. Function export berubah dari `middleware` ke `proxy` (named export `export function proxy()` atau default export `export default function proxy()` — keduanya valid).

## Metadata & SEO — next/head

```tsx
// Pages Router menggunakan next/head (BUKAN generateMetadata)
import Head from "next/head";

export default function UsersPage({ users }: Props) {
  return (
    <>
      <Head>
        <title>Users — My App</title>
        <meta name="description" content="Daftar semua user" />
        <meta property="og:title" content="Users" />
        <meta property="og:description" content="Daftar semua user" />
      </Head>

      <div>
        <h1>Users</h1>
        {/* ... */}
      </div>
    </>
  );
}

// Dynamic metadata
export default function UserDetailPage({ user }: Props) {
  return (
    <>
      <Head>
        <title>{user.name} — My App</title>
        <meta name="description" content={`Profil ${user.name}`} />
      </Head>

      <div>
        <h1>{user.name}</h1>
      </div>
    </>
  );
}
```

## Font (next/font)

```tsx
// pages/_app.tsx
import { Inter } from "next/font/google";
import type { AppProps } from "next/app";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  variable: "--font-inter",
});

export default function App({ Component, pageProps }: AppProps) {
  return (
    <div className={inter.variable}>
      <Component {...pageProps} />
    </div>
  );
}
```

## Dynamic Import (next/dynamic)

```tsx
import dynamic from "next/dynamic";

const HeavyChart = dynamic(() => import("@/components/HeavyChart"), {
  loading: () => <div className="h-64 animate-pulse">Loading chart...</div>,
  ssr: false, // disable SSR jika komponen hanya client-side
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

// next/image — otomatis lazy load, responsive, dan optimisasi format
<Image src="/hero.jpg" alt="Hero" width={800} height={400} priority />

// next/link — client-side navigation dengan prefetch otomatis
<Link href="/dashboard">Dashboard</Link>
```

## React Query untuk Client-Side Data

```tsx
// hooks/useUsers.ts
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

## Error Handling

```tsx
// lib/errors.ts — sama dengan App Router
export class AppError extends Error {
  constructor(
    message: string,
    public statusCode: number = 500,
    public code?: string,
  ) {
    super(message);
  }
}

// pages/api/users/[id].ts — penggunaan di API route
import { AppError } from "@/lib/errors";

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  try {
    const { id } = req.query;
    const user = await getUser(id as string);
    if (!user) throw new AppError("User tidak ditemukan", 404, "NOT_FOUND");
    return res.status(200).json({ data: user });
  } catch (error) {
    if (error instanceof AppError) {
      return res.status(error.statusCode).json({
        error: { message: error.message, code: error.code },
      });
    }
    console.error("Unexpected error:", error);
    return res.status(500).json({
      error: { message: "Terjadi kesalahan server", code: "INTERNAL_ERROR" },
    });
  }
}
```

## Custom Error Pages

```tsx
// pages/404.tsx
export default function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center text-center">
      <div>
        <h1 className="text-4xl font-bold">404</h1>
        <p className="mt-2">Halaman tidak ditemukan</p>
      </div>
    </div>
  );
}

// pages/500.tsx
export default function ServerErrorPage() {
  return (
    <div className="flex min-h-screen items-center justify-center text-center">
      <div>
        <h1 className="text-4xl font-bold">500</h1>
        <p className="mt-2">Terjadi kesalahan server</p>
      </div>
    </div>
  );
}
```

## Yang TIDAK Tersedia di Pages Router

Fitur-fitur berikut hanya ada di App Router:

| Fitur | Pages Router | App Router |
|-------|-------------|------------|
| Server Components | Tidak ada (semua client) | Default |
| Server Actions | Tidak ada | `"use server"` |
| `layout.tsx` per route | Tidak ada (`_app.tsx` global) | Per route segment |
| `loading.tsx` | Tidak ada | Automatic Suspense |
| `error.tsx` | Tidak ada | Per route error boundary |
| Route Groups `(name)` | Tidak ada | Organisasi tanpa URL |
| `generateMetadata` | Tidak ada (`next/head`) | Async metadata |
| React cache | Tidak ada | `React.cache()` |
| Streaming | Tidak ada | Built-in |
| Parallel Routes | Tidak ada | `@slot` convention |
| Intercepting Routes | Tidak ada | `(.)` convention |
| `"use cache"` directive | Tidak ada | Opt-in caching (v16) |

### Pages Router Advantages

- Lebih sederhana — tidak perlu berpikir client vs server
- `getServerSideProps`/`getStaticProps` jelas dan eksplisit
- Ecosystem library lebih mature dan compatible
- Debugging lebih straightforward
