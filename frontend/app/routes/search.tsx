import { useEffect, useState } from "react";
import { Link, useRevalidator, useSearchParams } from "react-router";
import type { Route } from "./+types/search";

interface Author {
  id: number;
  name: string;
}

interface LocalBook {
  id: number;
  title: string;
  cover_url: string | null;
  authors: Author[];
}

interface ExternalBook {
  title: string;
  author_names: string[];
  cover_url: string | null;
  open_library_key: string;
  importing: boolean;
}

export function meta({ params }: Route.MetaArgs) {
  return [{ title: "Ricerca - applibri" }];
}

export async function loader({ request }: Route.LoaderArgs) {
  const url = new URL(request.url);
  const q = url.searchParams.get("q") ?? "";
  const apiUrl = import.meta.env.VITE_API_URL;

  if (!q.trim()) {
    return { q, local: [], external: [] };
  }

  try {
    const res = await fetch(
      `${apiUrl}/books/search?q=${encodeURIComponent(q)}`
    );
    if (!res.ok) return { q, local: [], external: [] };
    const json = await res.json();
    return {
      q,
      local: json.local?.data ?? [],
      external: json.external ?? [],
    };
  } catch {
    return { q, local: [], external: [] };
  }
}

function BookCoverCard({
  title,
  coverUrl,
  authorLabel,
  isImporting,
  href,
}: {
  title: string;
  coverUrl: string | null;
  authorLabel: string | undefined;
  isImporting: boolean;
  href?: string;
}) {
  const content = (
    <>
      <div className="relative aspect-[2/3] w-full rounded-lg overflow-hidden bg-gray-200">
        {coverUrl ? (
          <img
            src={coverUrl}
            alt={title}
            className="w-full h-full object-cover"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="32"
              height="32"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.5"
            >
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
            </svg>
          </div>
        )}
        {isImporting && (
          <div className="absolute inset-0 bg-white/60 flex items-center justify-center">
            <div className="w-6 h-6 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
          </div>
        )}
      </div>
      <p className="mt-2 text-sm md:text-base font-medium line-clamp-2">
        {title}
      </p>
      {authorLabel && (
        <p className="text-xs text-gray-500 line-clamp-1">{authorLabel}</p>
      )}
      {isImporting && (
        <p className="text-xs text-amber-600 mt-0.5">Importazione in corso...</p>
      )}
    </>
  );

  if (href) {
    return (
      <Link to={href} className="block">
        {content}
      </Link>
    );
  }

  return <div>{content}</div>;
}

export default function SearchPage({ loaderData }: Route.ComponentProps) {
  const { q, local, external } = loaderData;
  const [searchParams] = useSearchParams();
  const revalidator = useRevalidator();

  const stillImporting = external.some((b: ExternalBook) => b.importing);

  // Poll every 3s while some external results are still importing,
  // so they migrate into "local" once the background job finishes.
  useEffect(() => {
    if (!stillImporting) return;

    const interval = setInterval(() => {
      revalidator.revalidate();
    }, 3000);

    return () => clearInterval(interval);
  }, [stillImporting, revalidator]);

  const totalResults = local.length + external.length;

  return (
    <main className="px-4 md:px-8 py-6 max-w-7xl mx-auto">
      <h1 className="text-xl font-bold mb-1">
        Risultati per &ldquo;{q}&rdquo;
      </h1>

      {totalResults === 0 && (
        <p className="text-sm text-gray-500 mt-4">
          Nessun risultato trovato.
        </p>
      )}

         {totalResults > 0 && (
        <section className="mt-6">
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
            {local.map((book: LocalBook) => (
              <BookCoverCard
                key={`local-${book.id}`}
                title={book.title}
                coverUrl={book.cover_url}
                authorLabel={book.authors?.[0]?.name}
                isImporting={false}
                href={`/books/${book.id}`}
              />
            ))}
            {external.map((book: ExternalBook) => (
              <BookCoverCard
                key={`external-${book.open_library_key}`}
                title={book.title}
                coverUrl={book.cover_url}
                authorLabel={book.author_names?.[0]}
                isImporting={book.importing}
              />
            ))}
          </div>
        </section>
      )}
    </main>
  );
}