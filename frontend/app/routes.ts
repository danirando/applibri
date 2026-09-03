import { type RouteConfig, index, route } from "@react-router/dev/routes";

export default [
  index("routes/home.tsx"),
  route("books/:id", "routes/book-detail.tsx"),
  route("search", "routes/search.tsx"),
  route("news", "routes/news.tsx"),
] satisfies RouteConfig;
