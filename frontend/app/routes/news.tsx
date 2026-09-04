import { useSearchParams } from "react-router";
import type { Route } from "./+types/news";

interface Article {
  id: number;
  title: string;
  excerpt: string | null;
  source_name: string;
  source_url: string;
  image_url: string | null;
  language: string;
  published_at: string;
}

export function meta() {
  return [{ title: "News - applibri" }];
}

export async function loader({ request }: Route.LoaderArgs) {
  const apiUrl = import.meta.env.VITE_API_URL;
  const url = new URL(request.url);
  const lang = url.searchParams.get("lang") === "en" ? "en" : "it";

  try {
    const res = await fetch(`${apiUrl}/articles?lang=${lang}`);
    if (!res.ok) return { articles: [], lang };
    const json = await res.json();
    return { articles: Array.isArray(json.data) ? json.data : [], lang };
  } catch {
    return { articles: [], lang };
  }
}

function ArticleCard({ article }: { article: Article }) {
  const date = new Date(article.published_at).toLocaleDateString("it-IT", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });

  return (
    <a
      href={article.source_url}
      target="_blank"
      rel="noopener noreferrer"
      className="block border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
      {article.image_url && (
        <div className="aspect-video w-full bg-gray-200">
          <img
            src={article.image_url}
            alt={article.title}
            className="w-full h-full object-cover"
          />
        </div>
      )}
      <div className="p-4">
        <div className="flex items-center gap-2 text-xs text-gray-500 mb-2">
          <span className="uppercase font-medium">{article.source_name}</span>
          <span>&middot;</span>
          <span>{date}</span>
        </div>
        <h3 className="font-semibold text-base mb-1 line-clamp-2">
          {article.title}
        </h3>
        {article.excerpt && (
          <p className="text-sm text-gray-600 line-clamp-3">
            {article.excerpt}
          </p>
        )}
      </div>
    </a>
  );
}

export default function NewsPage({
  loaderData,
}: {
  loaderData: { articles: Article[]; lang: string };
}) {
  const { articles, lang } = loaderData;
  const [, setSearchParams] = useSearchParams();

  return (
    <main className="px-4 md:px-8 py-6 max-w-5xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-xl font-bold">News</h1>

        <div className="inline-flex rounded-full border border-gray-300 overflow-hidden text-sm">
          <button
            onClick={() => setSearchParams({ lang: "it" })}
            className={`px-4 py-1.5 ${
              lang === "it"
                ? "bg-gray-800 text-white"
                : "bg-white text-gray-700"
            }`}>
            🇮🇹 IT
          </button>
          <button
            onClick={() => setSearchParams({ lang: "en" })}
            className={`px-4 py-1.5 ${
              lang === "en"
                ? "bg-gray-800 text-white"
                : "bg-white text-gray-700"
            }`}>
            🇬🇧 EN
          </button>
        </div>
      </div>

      {articles.length === 0 && (
        <p className="text-sm text-gray-500">Nessun articolo disponibile.</p>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {articles.map((article: Article) => (
          <ArticleCard key={article.id} article={article} />
        ))}
      </div>
    </main>
  );
}
