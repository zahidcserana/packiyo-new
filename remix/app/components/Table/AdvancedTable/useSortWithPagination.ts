import { useSearchParams } from "@remix-run/react";
import { CURRENT_PAGE } from "./Pagination/Pagination";

export const useSortWithPagination = () => {
  const [searchParams, setSearchParams] = useSearchParams();

  const sortable = searchParams.get("sort") ?? "";

  const isSortableExists = (resource: string) => {
    return  sortable.includes(resource);
  }

  const handleSort = (resource: string) => {
      const isSortableExists = sortable.includes(resource);

      if(!isSortableExists) {
          setSearchParams((prev) => {
              prev.set(CURRENT_PAGE, "1");
              prev.set("sort",resource);
              return prev;
          })
          return;
      }

      setSearchParams((prev) => {
          const resourceUpdated = sortable.includes("-") ? resource: `-${resource}`;
          prev.set(CURRENT_PAGE, "1");
          prev.set("sort",resourceUpdated);
          return prev;
      })
  }

  return { handleSort, isSortableExists, sortable }
}
