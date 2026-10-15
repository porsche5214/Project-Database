import { api } from "./client";
import type { ProductReview } from "../types/shop";

export const reviewsApi = {
  list: async (productId: number): Promise<ProductReview[]> => {
    const res = await api.get<{ reviews: ProductReview[]; total: number }>(`/products/${productId}/reviews`);
    return res.reviews || [];
  },
  submit: async (productId: number, data: { title?: string; text: string; rating?: number }): Promise<{ message: string; review: ProductReview; average_rating?: number }> => {
    return api.post<{ message: string; review: ProductReview; average_rating?: number }>(`/products/${productId}/reviews`, {
      title: data.title,
      comment: data.text,
      text: data.text,
      rating: data.rating,
      score: data.rating,
    });
  },
  rate: async (productId: number, score: number): Promise<{ message: string; average_rating: number; ratings_count: number }> => {
    return api.post<{ message: string; average_rating: number; ratings_count: number }>(`/products/${productId}/rating`, { score });
  },
  eligibility: async (productId: number): Promise<{ can_review: boolean; my_score: number | null }> => {
    return api.get<{ can_review: boolean; my_score: number | null }>(`/products/${productId}/eligibility`);
  },
};
