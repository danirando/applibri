import { Link } from "react-router";
import type { Route } from "./+types/book-detail";

interface Author {
  id: number;
  name: string;
}

interface Genre {
  id: number;
  name: string;
}

interface BookDetail {
  id: number;
  title: string;
  subtitle: string | null;
  description: string | null;
  cover_url: string | null;
  published_date: string | null;
  page_count: number | null;
  authors: Author[];
  genres: Genre[];
}

export function meta({ data }: { data?: { book: BookDetail } }) {
  return [
    { title: data?.book ? `${data.book.title} - applibri` : "applibri" },
  ];
}

export async function loader({ params }: Route.LoaderArgs) {
  const apiUrl = import.meta.env.VITE_API_URL;

  try {
    const res = await fetch(`${apiUrl}/books/${params.id}`);
    if (!res.ok) {
      throw new Response("Libro non trovato", { status: res.status });
    }
    const json = await res.json();
    const book: BookDetail = json.data ?? json;
    return { book };
  } catch (err) {
    if (err instanceof Response) throw err;
    throw new Response("Errore nel caricamento del libro", { status: 500 });
  }
}

export default function BookDetailPage({ loaderData }: Route.ComponentProps) {
  const { book } = loaderData;

  const year = book.published_date
    ? new Date(book.published_date).getFullYear()
    : null;

  return (
    <main className="px-4 md:px-8 py-6 max-w-4xl mx-auto">
      <Link to="/" className="text-sm text-gray-500 hover:underline">
        &larr; Torna alla home
      </Link>

      <div className="mt-4 flex flex-col sm:flex-row gap-6">
        {/* Cover */}
        <div className="w-40 sm:w-48 shrink-0 mx-auto sm:mx-0">
          <div className="aspect-[2/3] w-full rounded-lg overflow-hidden bg-gray-200">
            {book.cover_url ? (
              <img
                src={book.cover_url}
                alt={book.title}
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="40"
                  height="40"
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
          </div>
        </div>

       {/* Info */}
<div className="flex-1 min-w-0">
          <h1 className="text-2xl md:text-3xl font-bold">{book.title}</h1>
          {book.subtitle && (
            <p className="text-lg text-gray-500 mt-1">{book.subtitle}</p>
          )}

          {book.authors.length > 0 && (
            <p className="mt-2 text-gray-700">
              di{" "}
              {book.authors.map((a) => a.name).join(", ")}
            </p>
          )}

          <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
            {year && <span>{year}</span>}
            {book.page_count && <span>{book.page_count} pagine</span>}
          </div>

          {book.genres.length > 0 && (
            <div className="mt-3 flex flex-wrap gap-2">
              {book.genres.map((g) => (
                <span
                  key={g.id}
                  className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-full"
                >
                  {g.name}
                </span>
              ))}
            </div>
          )}

          {book.description && (
            <p className="mt-5 text-sm md:text-base text-gray-800 whitespace-pre-line">
              {book.description}
            </p>
          )}
        </div>
      </div>
    </main>
  );
}