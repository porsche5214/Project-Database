import { FormEvent, useState } from "react";
import type { Page } from "../types/shop";
import { Icon } from "./ui";

export function Header({ go, cartCount, wishCount, loggedIn, onSearch }: { go: (p: Page) => void; cartCount: number; wishCount: number; loggedIn: boolean; onSearch: (q: string) => void }) {
  const [menu, setMenu] = useState(false);
  const [query, setQuery] = useState("");
  const submit = (e: FormEvent) => { e.preventDefault(); onSearch(query.trim()); };
  return <header className="header">
    <button className="mobile-icon" onClick={() => setMenu(!menu)} aria-label="Menu" aria-expanded={menu}><Icon name={menu ? "close" : "menu"}/></button>
    <button className="logo" onClick={() => go("home")}>NAEMAK<span>แน่มาก</span></button>
    <nav className={menu ? "nav open" : "nav"}>
      <button onClick={() => { go("home"); setMenu(false); }}>Home</button>
      <button onClick={() => { go("products"); setMenu(false); }}>Products</button>
    </nav>
    <div className="header-actions">
      <form className="header-search" role="search" onSubmit={submit}>
        <Icon name="search" size={17}/>
        <input aria-label="Search products" value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search products..."/>
      </form>
      <button onClick={() => go("wishlist")} aria-label="Wishlist"><Icon name="heart"/>{wishCount > 0 && <b>{wishCount}</b>}</button>
      <button onClick={() => go("cart")} aria-label="Cart"><Icon name="bag"/>{cartCount > 0 && <b>{cartCount}</b>}</button>
      <button onClick={() => go(loggedIn ? "account" : "login")} aria-label={loggedIn ? "Open account" : "Log in"}><Icon name="user"/></button>
    </div>
  </header>;
}

export default Header;
