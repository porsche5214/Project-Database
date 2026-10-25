import { api } from "./client";
import type { Product } from "../types/shop";

export type ProductDetail = Product & {
  description: string;
  images: string[];
  variants: Array<{
    variant_id: number;
    product_id: number;
    color: string;
    size: string;
    sku: string;
    price: number;
    stock: number;
  }>;
  reviews_list: Array<{
    id: string;
    productId: number;
    reviewer: string;
    date: string;
    title?: string;
    text: string;
    rating: number;
  }>;
  myScore?: number | null;
  inWishlist?: boolean;
};

export type ProductFilterParams = {
  category?: string | number;
  brand?: string | number;
  gender?: string;
  size?: string;
  color?: string;
  min_price?: number;
  max_price?: number;
  q?: string;
  sort?: string;
  in_stock?: boolean;
};

export const productsApi = {
  list: async (params?: ProductFilterParams): Promise<Product[]> => {
    const query = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, val]) => {
        if (val !== undefined && val !== null && val !== "") {
          query.append(key, String(val));
        }
      });
    }
    const qStr = query.toString();
    const res = await api.get<{ data: Product[]; total: number }>(`/products${qStr ? `?${qStr}` : ""}`);
    return res.data || (res as unknown as Product[]);
  },
  get: (id: number): Promise<ProductDetail> => {
    return api.get<ProductDetail>(`/products/${id}`);
  },
};
