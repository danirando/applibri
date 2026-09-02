import { Link } from "react-router";
import type { Route } from "./+types/home";

export function meta({}: Route.MetaArgs) {
  return [
    { title: "applibri" },
    { name: "description", content: "Esplora libri, trame, autori e novità" },
  ];
}

interface Book {
  id: number;
  title: string;
  cover_url: string | null;
  authors: { id: number; name: string }[];
}

async function safeFetchList(url: string): Promise<Book[]> {
  try {
    const res = await fetch(url);
    if (!res.ok) return [];
    const json = await res.json();
    return Array.isArray(json.data) ? json.data : [];
  } catch {
    return [];
  }
}

async function safeFetchPopular(url: string): Promise<Book[]> {
  try {
    const res = await fetch(url);
    if (!res.ok) return [];
    const json = await res.json();
    const entries = json.data?.popular;
    if (!Array.isArray(entries)) return [];
    // Each entry is { rank, week_date, book: {...} } — extract the nested book
    return entries.map((entry: { book: Book }) => entry.book).filter(Boolean);
  } catch {
    return [];
  }
}

export async function loader() {
  const apiUrl = import.meta.env.VITE_API_URL;

  const [latest, bestSellers] = await Promise.all([
    safeFetchList(`${apiUrl}/books/latest`),
    safeFetchPopular(`${apiUrl}/best-sellers`),
  ]);

  return { latest, bestSellers };
}

function BookCard({ book }: { book: Book }) {
  const author = book.authors?.[0]?.name;

  return (
    <Link to={`/books/${book.id}`} className="group block">
      <div className="aspect-[2/3] w-full rounded-lg overflow-hidden bg-gray-200 transition-transform sm:group-hover:scale-105">
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
              width="32"
              height="32"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.5">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
            </svg>
          </div>
        )}
      </div>
      <p className="mt-2 text-sm md:text-base font-medium line-clamp-2">
        {book.title}
      </p>
      {author && <p className="text-xs text-gray-500 line-clamp-1">{author}</p>}
    </Link>
  );
}

function BookGrid({ books }: { books: Book[] }) {
  if (books.length === 0) {
    return <p className="text-sm text-gray-500">Nessun libro disponibile.</p>;
  }

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
      {books.map((book) => (
        <BookCard key={book.id} book={book} />
      ))}
    </div>
  );
}

export default function Home({ loaderData }: Route.ComponentProps) {
  const { latest, bestSellers } = loaderData;

  return (
    <main className="px-4 md:px-8 py-6 max-w-7xl mx-auto">
      <section className="mb-10">
        <h2 className="text-xl font-bold mb-4">Ultime uscite</h2>
        <BookGrid books={latest} />
      </section>

      <section>
        <h2 className="text-xl font-bold mb-4">Più popolari</h2>
        <BookGrid books={bestSellers.slice(0, 5)} />
      </section>
    </main>
  );
}
