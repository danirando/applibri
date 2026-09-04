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

async function safeFetchByListName(
  url: string,
  listName: string,
): Promise<Book[]> {
  try {
    const res = await fetch(url);
    if (!res.ok) return [];
    const json = await res.json();
    const entries = json.data?.[listName];
    if (!Array.isArray(entries)) return [];
    return entries.map((entry: { book: Book }) => entry.book).filter(Boolean);
  } catch {
    return [];
  }
}

export async function loader() {
  const apiUrl = import.meta.env.VITE_API_URL;

  const [trending, bestSellers] = await Promise.all([
    safeFetchByListName(`${apiUrl}/best-sellers`, "popular"),
    safeFetchByListName(`${apiUrl}/best-sellers`, "nyt"),
  ]);

  return { trending, bestSellers };
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
  const { trending, bestSellers } = loaderData;

  return (
    <main className="px-4 md:px-8 py-6 max-w-7xl mx-auto">
      <section className="mb-10">
        <h2 className="text-xl font-bold mb-4">I più venduti del momento</h2>
        <BookGrid books={bestSellers} />
      </section>

      <section>
        <h2 className="text-xl font-bold mb-4">Letture del momento</h2>
        <BookGrid books={trending} />
      </section>
    </main>
  );
}
