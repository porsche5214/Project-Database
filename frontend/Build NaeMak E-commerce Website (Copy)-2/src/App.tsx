import { ReactNode, useEffect, useMemo, useState } from "react";
import {
  createBrowserRouter,
  Navigate,
  Outlet,
  RouterProvider,
  useNavigate,
  useOutletContext,
  useParams,
} from "react-router-dom";
import Header from "./components/Header";
import ProductCard from "./components/ProductCard";
import { Button, Icon } from "./components/ui";
import { products } from "./data/products";
import { initialReviews } from "./data/reviews";
import AccountPage from "./pages/AccountPage";
import CartPage from "./pages/CartPage";
import CheckoutPage from "./pages/CheckoutPage";
import HomePage from "./pages/HomePage";
import LoginPage from "./pages/LoginPage";
import ProductDetailPage from "./pages/ProductDetailPage";
import ProductsPage from "./pages/ProductsPage";
import RegisterPage from "./pages/RegisterPage";
import WishlistPage from "./pages/WishlistPage";
import { cartItemKey, type CartItem, type MemberInfo, type Page, type Product } from "./types/shop";
import type { ProductReview } from "./types/shop";

type ShopContext = {
  add: (product: Product, color?: string, size?: string, quantity?: number) => void;
  cart: CartItem[];
  cards: (list: Product[]) => ReactNode[];
  filtered: Product[];
  go: (page: Page) => void;
  loggedIn: boolean;
  memberInfo: MemberInfo;
  query: string;
  reviews: Record<number, ProductReview[]>;
  setCart: React.Dispatch<React.SetStateAction<CartItem[]>>;
  setLoggedIn: React.Dispatch<React.SetStateAction<boolean>>;
  setMemberInfo: React.Dispatch<React.SetStateAction<MemberInfo>>;
  setQuery: React.Dispatch<React.SetStateAction<string>>;
  toggleWish: (id: number) => void;
  submitReview: (productId: number, review: Pick<ProductReview, "title" | "text" | "rating">) => void;
  view: (product: Product) => void;
  wishlist: number[];
};

const pagePaths: Record<Page, string> = {
  home: "/",
  products: "/products",
  detail: "/products/1",
  wishlist: "/wishlist",
  cart: "/cart",
  checkout: "/checkout",
  confirmed: "/order-confirmation",
  login: "/login",
  register: "/register",
  account: "/account",
  admin: "/admin",
};

function useStoredState<T>(key: string, initialValue: T) {
  const [value, setValue] = useState<T>(() => {
    try {
      const stored = window.localStorage.getItem(key);
      return stored ? JSON.parse(stored) as T : initialValue;
    } catch {
      return initialValue;
    }
  });

  useEffect(() => {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
      // The storefront still works when storage is unavailable.
    }
  }, [key, value]);

  return [value, setValue] as const;
}

function ShopLayout() {
  const navigate = useNavigate();
  const [wishlist, setWishlist] = useStoredState<number[]>("naemak:wishlist", [3]);
  const [cart, setCart] = useStoredState<CartItem[]>("naemak:cart", []);
  const [reviews, setReviews] = useStoredState<Record<number, ProductReview[]>>("naemak:reviews", initialReviews);
  const [loggedIn, setLoggedIn] = useStoredState("naemak:logged-in", false);
  const [query, setQuery] = useState("");
  const [toast, setToast] = useState("");
  const [memberInfo, setMemberInfo] = useStoredState<MemberInfo>("naemak:member", { email:"araya@naemak.com", phone:"+66 81 234 5678", firstName:"Araya", lastName:"Siriporn", address:"88 Sukhumvit Road, Khlong Toei", city:"Bangkok", province:"Bangkok", postalCode:"10110", country:"Thailand" });
  const go = (page: Page) => { navigate(pagePaths[page]); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const flash = (s: string) => { setToast(s); window.setTimeout(() => setToast(""), 2400); };
  const view = (product: Product) => { navigate(`/products/${product.id}`); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const toggleWish = (id: number) => {
    if (!loggedIn) { go("login"); return; }
    setWishlist((w) => w.includes(id) ? w.filter((x) => x !== id) : [...w, id]); flash("Wishlist updated");
  };
  const add = (p: Product, color = p.colors[0], size = p.sizes[0], quantity = 1) => {
    const nextItem = { product: p, qty: quantity, color, size };
    const nextKey = cartItemKey(nextItem);
    setCart((items) => {
      const found = items.some((item) => cartItemKey(item) === nextKey);
      return found
        ? items.map((item) => cartItemKey(item) === nextKey ? {...item, qty: item.qty + quantity} : item)
        : [...items, nextItem];
    });
    flash(`${p.name} added to bag`);
  };
  const submitReview = (productId: number, review: Pick<ProductReview, "title" | "text" | "rating">) => {
    const date = new Intl.DateTimeFormat("en-GB", { day: "2-digit", month: "short", year: "numeric" }).format(new Date()).toUpperCase();
    const nextReview: ProductReview = {
      ...review,
      id: `${productId}-${Date.now()}`,
      productId,
      reviewer: `${memberInfo.firstName} ${memberInfo.lastName.charAt(0)}.`,
      date,
    };
    setReviews((current) => ({ ...current, [productId]: [nextReview, ...(current[productId] || [])] }));
    flash("Review published");
  };
  const filtered = useMemo(() => products.filter((p) => p.name.toLowerCase().includes(query.toLowerCase())), [query]);
  const cards = (list: Product[]) => list.map((p) => <ProductCard key={p.id} product={p} wishlisted={wishlist.includes(p.id)} onWish={() => toggleWish(p.id)} onView={() => view(p)} onAdd={() => add(p)}/>);
  const context: ShopContext = {
    add, cart, cards, filtered, go, loggedIn, memberInfo, query, reviews, setCart, setLoggedIn,
    setMemberInfo, setQuery, submitReview, toggleWish, view, wishlist,
  };

  return <><Header go={go} cartCount={cart.reduce((s,x)=>s+x.qty,0)} wishCount={wishlist.length} loggedIn={loggedIn} onSearch={(q) => { setQuery(q); go("products"); }}/><Outlet context={context}/>{toast && <div className="toast"><Icon name="check"/>{toast}</div>}</>;
}

function useShop() {
  return useOutletContext<ShopContext>();
}

function HomeRoute() {
  const { cards, go } = useShop();
  return <HomePage go={go} cards={cards(products.slice(0, 4))}/>;
}

function ProductsRoute() {
  const { cards, filtered, query, setQuery } = useShop();
  return <ProductsPage products={filtered} renderCards={cards} query={query} setQuery={setQuery}/>;
}

function ProductDetailRoute() {
  const { productId } = useParams();
  const { add, go, loggedIn, reviews, submitReview, toggleWish } = useShop();
  const product = products.find((item) => item.id === Number(productId));
  if (!product) return <Navigate to="/products" replace/>;
  return <ProductDetailPage product={product} onAdd={(color,size,quantity) => add(product,color,size,quantity)} onWish={() => toggleWish(product.id)} go={go} loggedIn={loggedIn} reviews={reviews[product.id] || []} onSubmitReview={(review) => submitReview(product.id, review)}/>;
}

function WishlistRoute() {
  const { add, go, loggedIn, toggleWish, view, wishlist } = useShop();
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <WishlistPage products={products.filter((product) => wishlist.includes(product.id))} onRemove={toggleWish} onAdd={(product, size) => add(product, product.colors[0], size)} onView={view} go={go}/>;
}

function CartRoute() {
  const { cart, go, setCart } = useShop();
  return <CartPage items={cart} go={go} remove={(key) => setCart((items) => items.filter((item) => cartItemKey(item) !== key))} updateQty={(key,difference) => setCart((items) => items.map((item) => cartItemKey(item) === key ? {...item, qty: Math.max(1, item.qty+difference)} : item))}/>;
}

function CheckoutRoute() {
  const { cart, go, loggedIn, memberInfo, setMemberInfo } = useShop();
  if (!cart.length) return <Navigate to="/cart" replace/>;
  return <CheckoutPage items={cart} go={go} loggedIn={loggedIn} memberInfo={memberInfo} onSaveInfo={setMemberInfo}/>;
}

function ConfirmationRoute() {
  const { cart, go } = useShop();
  return <main><section className="confirmation"><i><Icon name="check" size={36}/></i><span className="eyebrow">THANK YOU, ARAYA</span><h1>ORDER PLACED<br/>SUCCESSFULLY.</h1><p>Your order <b>#NM-250621-0914</b> is confirmed.<br/>We’ll let you know when it’s on its way.</p><div><span>ORDER DATE <b>21 JUN 2025</b></span><span>TOTAL <b>฿{cart.reduce((sum,item)=>sum+item.product.price*item.qty,0).toLocaleString()}</b></span><span>STATUS <b>PAID</b></span></div><Button onClick={() => go("account")}>VIEW MY ORDERS</Button></section></main>;
}

function LoginRoute() {
  const { go, setLoggedIn } = useShop();
  return <LoginPage go={go} onSuccess={() => { setLoggedIn(true); go("home"); }}/>;
}

function RegisterRoute() {
  const { go, setLoggedIn } = useShop();
  return <RegisterPage go={go} onSuccess={() => { setLoggedIn(true); go("home"); }}/>;
}

function AccountRoute() {
  const { go, loggedIn, memberInfo, setLoggedIn, setMemberInfo } = useShop();
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <AccountPage memberInfo={memberInfo} go={go} onSaveInfo={setMemberInfo} onLogout={() => { setLoggedIn(false); go("home"); }}/>;
}

function NotFoundRoute() {
  return <Navigate to="/" replace/>;
}

const router = createBrowserRouter([
  {
    path: "/",
    Component: ShopLayout,
    children: [
      { index: true, Component: HomeRoute },
      { path: "products", Component: ProductsRoute },
      { path: "products/:productId", Component: ProductDetailRoute },
      { path: "wishlist", Component: WishlistRoute },
      { path: "cart", Component: CartRoute },
      { path: "checkout", Component: CheckoutRoute },
      { path: "order-confirmation", Component: ConfirmationRoute },
      { path: "login", Component: LoginRoute },
      { path: "register", Component: RegisterRoute },
      { path: "account", Component: AccountRoute },
      { path: "admin", Component: AccountRoute },
      { path: "*", Component: NotFoundRoute },
    ],
  },
]);

export default function App() {
  return <RouterProvider router={router}/>;
}
