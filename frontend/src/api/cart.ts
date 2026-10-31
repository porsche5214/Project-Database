import { api } from "./client";

export type ApiCartItem = {
  variant_id: number;
  product_id: number;
  name: string;
  brand: string;
  image: string;
  color: string;
  size: string;
  price: number;
  quantity: number;
  total: number;
  stock: number;
};

export type CartResponse = {
  cart_id: number;
  items: ApiCartItem[];
  subtotal: number;
  shipping: number;
  total: number;
};

export const cartApi = {
  get: (): Promise<CartResponse> => {
    return api.get<CartResponse>('/cart');
  },
  add: (data: { variant_id?: number; product_id?: number; color?: string; size?: string; quantity: number }): Promise<{ message: string; cart: CartResponse }> => {
    return api.post<{ message: string; cart: CartResponse }>('/cart/add', data);
  },
  update: (variantId: number, quantity: number, extra?: { product_id?: number; color?: string; size?: string }): Promise<{ message: string; cart: CartResponse }> => {
    return api.post<{ message: string; cart: CartResponse }>(`/cart/update/${variantId}`, { quantity, ...extra });
  },
  remove: (variantId: number, extra?: { product_id?: number; color?: string; size?: string }): Promise<{ message: string; cart: CartResponse }> => {
    return api.post<{ message: string; cart: CartResponse }>(`/cart/remove/${variantId}`, extra || {});
  },
  clear: (): Promise<{ message: string; cart: CartResponse }> => {
    return api.post<{ message: string; cart: CartResponse }>('/cart/clear', {});
  },
};
