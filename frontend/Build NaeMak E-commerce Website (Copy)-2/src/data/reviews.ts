import type { ProductReview } from "../types/shop";

export const initialReviews: Record<number, ProductReview[]> = {
  1: [
    { id: "1-mali", productId: 1, reviewer: "Mali K.", date: "12 JUN 2025", title: "THE PERFECT STRUCTURE", text: "Perfect structure without feeling stiff. The cut is oversized in exactly the right way.", rating: 5 },
    { id: "1-ploy", productId: 1, reviewer: "Ploy N.", date: "28 MAY 2025", text: "Beautiful fabric and the fit is true to size. Already planning my next color.", rating: 5 },
  ],
  2: [
    { id: "2-kanya", productId: 2, reviewer: "Kanya S.", date: "12 OCT 2026", title: "PREMIUM EVERYDAY TEE", text: "Great structure and the fabric feels premium. The fit is true to size.", rating: 5 },
    { id: "2-narin", productId: 2, reviewer: "Narin P.", date: "03 OCT 2026", text: "A clean shape that holds up beautifully after washing.", rating: 4 },
  ],
};
