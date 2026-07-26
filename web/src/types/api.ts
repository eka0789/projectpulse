export type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type ApiResponse<T, TMeta = Record<string, unknown> | null> = {
  success: boolean;
  message: string;
  data: T;
  meta: TMeta;
};

export type PaginatedResponse<T> = ApiResponse<T[], PaginationMeta>;
