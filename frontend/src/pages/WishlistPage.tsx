import { useState, type ReactNode } from "react";
import ProductCard from "../components/ProductCard";
import { Button, Empty } from "../components/ui";
import type { Page, Product } from "../types/shop";

function WishlistItem({ product, onRemove, onAdd, onView }: { product: Product; onRemove: () => void; onAdd: (size: string) => void; onView: () => void }) {
  const [size, setSize] = useState(product.sizes[0]);
  const actions: ReactNode = <div className="wishlist-actions"><label><span>SIZE</span><select value={size} onChange={(event) => setSize(event.target.value)}>{product.sizes.map((option) => <option key={option}>{option}</option>)}</select></label><button onClick={onRemove}>REMOVE</button><Button onClick={() => onAdd(size)}>ADD TO BAG</Button></div>;
  return <ProductCard product={product} wishlisted onWish={onRemove} onView={onView} onAdd={() => onAdd(size)} footer={actions}/>;
}

export default function WishlistPage({ products, onRemove, onAdd, onView, go }: { products: Product[]; onRemove: (id: number) => void; onAdd: (product: Product, size: string) => void; onView: (product: Product) => void; go: (page: Page) => void }) {
  return <main className="wishlist-page"><div className="page-heading"><span className="eyebrow">YOUR SAVED ITEMS</span><h1>WISHLIST</h1><p>{products.length} {products.length === 1 ? "piece" : "pieces"} saved for later.</p></div>{products.length ? <div className="product-grid wishlist-grid">{products.map((product) => <WishlistItem key={product.id} product={product} onRemove={() => onRemove(product.id)} onAdd={(size) => onAdd(product, size)} onView={() => onView(product)}/>)}</div> : <Empty title="Your wishlist is empty." text="Save the pieces you love and come back to them anytime." action="EXPLORE PRODUCTS" onAction={() => go("products")}/>}</main>;
}
