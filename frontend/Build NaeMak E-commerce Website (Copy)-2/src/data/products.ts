import type { Product } from "../types/shop";
import look1 from "../assets/products/look-1.jpg";
import look2 from "../assets/products/look-2.jpg";
import look3 from "../assets/products/look-3.jpg";
import look4 from "../assets/products/look-4.jpg";
import look5 from "../assets/products/look-5.jpg";
import look6 from "../assets/products/look-6.jpg";
import look7 from "../assets/products/look-7.jpg";
import look8 from "../assets/products/look-8.jpg";

export const photos = [
  look1,
  look2,
  look3,
  look4,
  look5,
  look6,
  look7,
  look8,
];

export const products: Product[] = [
  { id: 1, name: "Structure Blazer", brand: "NAEMAK STUDIO", price: 3290, category: "Outerwear", gender: "Women", rating: 4.8, reviews: 124, image: photos[0], badge: "NEW", colors: ["Black", "Stone"], sizes: ["XS", "S", "M", "L"] },
  { id: 2, name: "Essential Box Tee", brand: "NAEMAK", price: 890, category: "T-Shirts", gender: "Unisex", rating: 4.7, reviews: 86, image: photos[1], badge: "BESTSELLER", colors: ["Black", "White", "Gray"], sizes: ["S", "M", "L", "XL"] },
  { id: 3, name: "Noir Column Dress", brand: "NAEMAK STUDIO", price: 2590, category: "Women", gender: "Women", rating: 4.9, reviews: 48, image: photos[2], colors: ["Black"], sizes: ["XS", "S", "M", "L"] },
  { id: 4, name: "Soft Form Cardigan", brand: "NAEMAK", price: 1890, category: "Outerwear", gender: "Women", rating: 4.6, reviews: 72, image: photos[3], badge: "NEW", colors: ["Black", "Cream"], sizes: ["S", "M", "L"] },
  { id: 5, name: "Everyday Straight Pant", brand: "NAEMAK", price: 1690, category: "Pants", gender: "Unisex", rating: 4.5, reviews: 61, image: photos[4], colors: ["Black", "Charcoal"], sizes: ["S", "M", "L", "XL"] },
  { id: 6, name: "Second Skin Top", brand: "N/M ESSENTIALS", price: 1190, category: "Shirts", gender: "Women", rating: 4.7, reviews: 39, image: photos[5], colors: ["Black", "White"], sizes: ["XS", "S", "M"] },
  { id: 7, name: "Rail Utility Trouser", brand: "N/M ESSENTIALS", price: 1990, category: "Pants", gender: "Unisex", rating: 4.6, reviews: 31, image: photos[6], badge: "LIMITED", colors: ["Black", "Olive"], sizes: ["S", "M", "L"] },
  { id: 8, name: "Clean Line Shirt", brand: "NAEMAK STUDIO", price: 1490, category: "Shirts", gender: "Unisex", rating: 4.8, reviews: 55, image: photos[7], colors: ["White", "Black"], sizes: ["S", "M", "L", "XL"] },
];
