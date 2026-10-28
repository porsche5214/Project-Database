export type Page = "home" | "products" | "detail" | "wishlist" | "cart" | "checkout" | "confirmed" | "login" | "register" | "account" | "admin";

export type Product = {
  id: number; name: string; brand: string; price: number; category: string; gender: string;
  rating: number; reviews: number; image: string; badge?: string; colors: string[]; sizes: string[];
  in_stock?: boolean;
};

export type MemberInfo = {
  email: string; phone: string; firstName: string; lastName: string; address: string;
  city: string; province: string; postalCode: string; country: string;
};

export type CartItem = { product: Product; qty: number; color: string; size: string };

export type ProductReview = {
  id: string;
  productId: number;
  reviewer: string;
  date: string;
  title?: string;
  text: string;
  rating: number;
};

export function cartItemKey(item: Pick<CartItem, "product" | "color" | "size">) {
  return `${item.product.id}:${item.color}:${item.size}`;
}
