import { ReactNode, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";
import Filter from "../components/Filter";
import { Empty, Icon } from "../components/ui";
import type { Product } from "../types/shop";

const SIZE_ORDER = ["XS", "S", "M", "L", "XL", "XXL", "Free"];
const GENDER_OPTIONS = [{ value: "male", label: "Men" }, { value: "female", label: "Women" }, { value: "unisex", label: "Unisex" }];

export default function ProductsPage({ products, renderCards, query, setQuery }: { products: Product[]; renderCards: (items: Product[]) => ReactNode[]; query: string; setQuery: (v: string) => void }) {
  const [filters, setFilters] = useState(false);
  // ?category=Hoodie,Sweater&gender=male (e.g. from the home page tiles) pre-selects filters.
  const [searchParams, setSearchParams] = useSearchParams();
  const fromUrl = (key: string) => (searchParams.get(key) || "").split(",").filter(Boolean);
  const [categories, setCategories] = useState<string[]>(() => fromUrl("category"));
  const [genders, setGenders] = useState<string[]>(() => fromUrl("gender"));
  useEffect(() => { setCategories(fromUrl("category")); setGenders(fromUrl("gender")); }, [searchParams]);
  const [brands, setBrands] = useState<string[]>([]);
  const [sizes, setSizes] = useState<string[]>([]);
  const [colors, setColors] = useState<string[]>([]);
  const [inStockOnly, setInStockOnly] = useState(false);
  // Filter options come from the products the API returned, so every option can match something.
  const options = useMemo(() => {
    const unique = (values: string[]) => [...new Set(values)].sort((a, b) => a.localeCompare(b));
    const sizeRank = (size: string) => { const i = SIZE_ORDER.indexOf(size); return i === -1 ? 100 + (Number(size) || 0) : i; };
    return {
      categories: unique(products.map((p) => p.category)),
      genders: GENDER_OPTIONS.filter((g) => products.some((p) => p.gender === g.value)),
      brands: unique(products.map((p) => p.brand)),
      sizes: [...new Set(products.flatMap((p) => p.sizes))].sort((a, b) => sizeRank(a) - sizeRank(b) || a.localeCompare(b)),
      colors: unique(products.flatMap((p) => p.colors)),
      priceCap: Math.max(1000, Math.ceil(Math.max(0, ...products.map((p) => p.price)) / 500) * 500),
    };
  }, [products]);
  const [maxPrice, setMaxPrice] = useState<number | null>(null);
  const priceLimit = maxPrice ?? options.priceCap;
  const [sort, setSort] = useState("recommended");
  const toggle = (value: string, selected: string[], update: (values: string[]) => void) => update(selected.includes(value) ? selected.filter((item) => item !== value) : [...selected, value]);
  const visibleProducts = useMemo(() => {
    const matching = products.filter((product) => {
      const categoryMatch = !categories.length || categories.includes(product.category);
      // Unisex items show up under Men and Women too.
      const genderMatch = !genders.length || genders.includes(product.gender) || product.gender === "unisex";
      const brandMatch = !brands.length || brands.includes(product.brand);
      const sizeMatch = !sizes.length || sizes.some((size) => product.sizes.includes(size));
      const colorMatch = !colors.length || colors.some((color) => product.colors.includes(color));
      const stockMatch = !inStockOnly || product.in_stock !== false;
      return categoryMatch && genderMatch && brandMatch && sizeMatch && colorMatch && stockMatch && product.price <= priceLimit;
    });
    return [...matching].sort((a, b) => {
      if (sort === "newest") return b.id - a.id;
      if (sort === "price-asc") return a.price - b.price;
      if (sort === "price-desc") return b.price - a.price;
      if (sort === "rating") return b.rating - a.rating;
      return 0;
    });
  }, [brands, categories, colors, genders, inStockOnly, priceLimit, products, sizes, sort]);
  const activeFilters = categories.length + genders.length + brands.length + sizes.length + colors.length + (inStockOnly ? 1 : 0) + (maxPrice !== null ? 1 : 0);
  const clearFilters = () => {
    setSearchParams({});
    setQuery("");
    setCategories([]);
    setGenders([]);
    setBrands([]);
    setSizes([]);
    setColors([]);
    setInStockOnly(false);
    setMaxPrice(null);
    setSort("recommended");
  };
  const cards = renderCards(visibleProducts);
  return <main className="listing-page">
    <div className="listing-head"><span className="eyebrow">NAEMAK COLLECTION</span><h1>{query ? `RESULTS FOR “${query.toUpperCase()}”` : "ALL PRODUCTS"}</h1><p>{cards.length} pieces — designed for wherever you go.</p></div>
    <div className="listing-toolbar"><button className="filter-toggle" onClick={() => setFilters(!filters)} aria-expanded={filters}><Icon name="filter"/> FILTERS</button><span>{cards.length} PRODUCTS</span><label>SORT BY <select value={sort} onChange={(event) => setSort(event.target.value)}><option value="recommended">Recommended</option><option value="newest">Newest</option><option value="price-asc">Price: Low to High</option><option value="price-desc">Price: High to Low</option><option value="rating">Highest Rated</option></select></label></div>
    <div className="listing-layout">
      <aside className={filters ? "filters open" : "filters"}>
        {query && <button className="clear-search" onClick={() => setQuery("")}>CLEAR SEARCH ×</button>}
        {activeFilters > 0 && <button className="clear-search" onClick={clearFilters}>CLEAR FILTERS ({activeFilters}) ×</button>}
        <Filter title="CATEGORY" options={options.categories} selected={categories} onToggle={(value) => toggle(value, categories, setCategories)}/>
        <Filter title="GENDER" options={options.genders.map((g) => g.value)} label={(value) => GENDER_OPTIONS.find((g) => g.value === value)?.label ?? value} selected={genders} onToggle={(value) => toggle(value, genders, setGenders)}/>
        <Filter title="BRAND" options={options.brands} selected={brands} onToggle={(value) => toggle(value, brands, setBrands)}/>
        <Filter title="SIZE" options={options.sizes} selected={sizes} onToggle={(value) => toggle(value, sizes, setSizes)} chips/>
        <div className="filter-group"><strong>COLOR</strong><div className="swatches">{options.colors.map((color) => <button key={color} className={`${color.toLowerCase()} ${colors.includes(color) ? "selected" : ""}`} onClick={() => toggle(color, colors, setColors)} aria-label={color} title={color} aria-pressed={colors.includes(color)}/>)}</div>{colors.length > 0 && <small>{colors.join(", ")}</small>}</div>
        <div className="filter-group"><strong>PRICE</strong><div className="price-inputs"><span>฿ 0</span><span>UP TO ฿ {priceLimit.toLocaleString()}</span></div><input type="range" min="0" max={options.priceCap} step="100" value={priceLimit} onChange={(event) => setMaxPrice(Number(event.target.value))} aria-label="Maximum price"/></div>
        <div className="filter-group"><strong>AVAILABILITY</strong><div className="filter-options"><label><input type="checkbox" checked={inStockOnly} onChange={(event) => setInStockOnly(event.target.checked)}/><span>In stock only</span></label></div></div>
      </aside>
      {cards.length ? <div className="product-grid listing-grid">{cards}</div> : <Empty title="No products found." text="Try a different name, brand, or category." action="CLEAR FILTERS" onAction={clearFilters}/>}
    </div>
  </main>;
}
