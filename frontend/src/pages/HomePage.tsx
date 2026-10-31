import type { ReactNode } from "react";
import { useNavigate } from "react-router-dom";
import { Button, Icon, SectionTitle } from "../components/ui";
import { photos } from "../assets/photos";
import type { Page } from "../types/shop";

// Each tile opens the products page with matching filters (read by ProductsPage from the URL).
const CATEGORY_TILES = [
  { label: "Men", query: "gender=male" },
  { label: "Women", query: "gender=female" },
  { label: "T-Shirts", query: "category=T-shirt" },
  { label: "Outerwear", query: "category=Hoodie,Sweater" },
  { label: "Pants & Skirts", query: "category=Pants,Skirt" },
  { label: "Shoes", query: "category=Lifestyle,Sport" },
];

export default function HomePage({ go, cards }: { go: (p: Page) => void; cards: ReactNode }) {
  const navigate = useNavigate();
  const openCategory = (query: string) => { navigate(`/products?${query}`); window.scrollTo({ top: 0 }); };
  return <main>
    <section className="hero">
      <img src={photos[4]} alt="NaeMak monochrome streetwear campaign"/>
      <div className="hero-shade"/>
      <div className="hero-copy"><span className="eyebrow light">NAEMAK — NEW SEASON 2025</span><h1>BE BOLD.<br/>BE NAEMAK.</h1><p>Everyday style, made to stand out.</p><div className="hero-buttons"><Button variant="light" onClick={() => go("products")}>SHOP NOW</Button><Button variant="outline" onClick={() => go("products")}>EXPLORE COLLECTION</Button></div></div>
      <div className="hero-index">01 / 04</div>
    </section>
    <section className="marquee"><span>NEW FORMS</span><i/> <span>MADE IN BANGKOK</span><i/> <span>EVERYDAY ICONS</span><i/> <span>UNCOMPROMISED FIT</span></section>
    <section className="section">
      <SectionTitle kicker="SHOP BY" title="CATEGORIES" action="VIEW ALL" onAction={() => go("products")}/>
      <div className="category-grid">{CATEGORY_TILES.map((c, i) => <button className={`category-card c${i}`} key={c.label} onClick={() => openCategory(c.query)}><img src={photos[(i + 1) % photos.length]} alt=""/><span>{c.label}</span><i><Icon name="arrow"/></i></button>)}</div>
    </section>
    <section className="section product-section">
      <SectionTitle kicker="THE EDIT" title="FEATURED PIECES" action="SHOP ALL" onAction={() => go("products")}/>
      <div className="product-grid">{cards}</div>
    </section>
    <section className="campaign">
      <div className="campaign-copy"><span className="eyebrow light">NAEMAK EDITORIAL / 02</span><h2>FIND YOUR<br/>FORM.</h2><p>Designed for the city. Built for your every day.</p><Button variant="light" onClick={() => go("products")}>SHOP COLLECTION</Button></div>
      <img src={photos[2]} alt="NaeMak Find Your Form campaign"/>
    </section>
  </main>;
}
