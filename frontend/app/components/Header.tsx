import { useState } from "react";
import { useNavigate, useNavigation } from "react-router";

export function Header() {
  const [showSearch, setShowSearch] = useState(false);
  const navigate = useNavigate();
  const navigation = useNavigation();

  const isSearching =
    navigation.state === "loading" &&
    navigation.location?.pathname === "/search";

  function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const q = formData.get("q")?.toString().trim();
    if (q) {
      navigate(`/search?q=${encodeURIComponent(q)}`);
    }
  }

  return (
    <header className="sticky top-0 z-10 bg-white shadow-sm px-4 md:px-8 py-3">
      <div className="flex items-center justify-between gap-4">
        <a href="/" className="font-bold text-lg shrink-0">
          applibri
        </a>

        <a
          href="/news"
          className="hidden md:inline text-sm text-gray-600 hover:text-gray-900 shrink-0">
          News
        </a>

        {/* Search bar: always visible from sm: up */}
        <form
          onSubmit={handleSubmit}
          className="hidden sm:flex flex-1 max-w-md">
          <input
            type="text"
            name="q"
            placeholder="Cerca libri o autori..."
            className="flex-1 border border-gray-300 rounded-l-full px-4 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400"
          />
          <button
            type="submit"
            disabled={isSearching}
            className="bg-gray-800 text-white rounded-r-full px-4 text-sm disabled:opacity-60 flex items-center justify-center min-w-[60px]">
            {isSearching ? (
              <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              "Cerca"
            )}
          </button>
        </form>

        {/* Search icon toggle: mobile only */}
        <button
          type="button"
          onClick={() => setShowSearch((v) => !v)}
          className="sm:hidden p-2"
          aria-label="Cerca">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </button>
      </div>

      {/* Expanded search bar: mobile only, toggled */}
      {showSearch && (
        <form onSubmit={handleSubmit} className="sm:hidden mt-3 flex">
          <input
            type="text"
            name="q"
            placeholder="Cerca libri o autori..."
            className="flex-1 border border-gray-300 rounded-l-full px-4 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400"
            autoFocus
          />
          <button
            type="submit"
            disabled={isSearching}
            className="bg-gray-800 text-white rounded-r-full px-4 text-sm disabled:opacity-60 flex items-center justify-center min-w-[60px]">
            {isSearching ? (
              <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              "Cerca"
            )}
          </button>
        </form>
      )}
    </header>
  );
}
