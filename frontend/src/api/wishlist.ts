import { api } from "./client";
import type { Product } from "../types/shop";

export type WishlistResponse = {
  ids: number[];
  products: Product[];
};

export const wishlistApi = {
  get: (): Promise<WishlistResponse> => {
    return api.get<WishlistResponse>('/wishlist');
  },
  add: (id: number): Promise<{ message: string; ids: number[] }> => {
    return api.post<{ message: string; ids: number[] }>(`/wishlist/${id}`, {});
  },
  remove: (id: number): Promise<{ message: string; ids: number[] }> => {
    return api.delete<{ message: string; ids: number[] }>(`/wishlist/${id}`);
  },
  toggle: (id: number): Promise<{ message: string; in_wishlist: boolean; ids: number[] }> => {
    return api.post<{ message: string; in_wishlist: boolean; ids: number[] }>(`/wishlist/${id}/toggle`, {});
  },
};
