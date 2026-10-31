import { productsApi, type ProductDetail } from "./api/products";
import { cartApi, type ApiCartItem } from "./api/cart";
import { wishlistApi } from "./api/wishlist";
import { ordersApi, type ApiOrder } from "./api/orders";
import { reviewsApi } from "./api/reviews";
import { authApi, mapUserToMemberInfo } from "./api/auth";
import { api } from "./api/client";
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
  allProducts: Product[];
  productsLoading: boolean;
  lastOrder: ApiOrder | null;
  setLastOrder: React.Dispatch<React.SetStateAction<ApiOrder | null>>;
  go: (page: Page) => void;
  flash: (message: string) => void;
  authReady: boolean;
  loggedIn: boolean;
  memberInfo: MemberInfo;
  query: string;
  reviews: Record<number, ProductReview[]>;
  setReviews: React.Dispatch<React.SetStateAction<Record<number, ProductReview[]>>>;
  setCart: React.Dispatch<React.SetStateAction<CartItem[]>>;
  setLoggedIn: React.Dispatch<React.SetStateAction<boolean>>;
  setMemberInfo: React.Dispatch<React.SetStateAction<MemberInfo>>;
  setQuery: React.Dispatch<React.SetStateAction<string>>;
  toggleWish: (id: number) => void;
  submitReview: (productId: number, review: Pick<ProductReview, "title" | "text" | "rating">) => Promise<boolean>;
  view: (product: Product) => void;
  wishlist: number[];
  resetSession: () => void;
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
  orders: "/account?tab=orders",
  admin: "/admin",
};

const emptyMemberInfo: MemberInfo = { email: "", phone: "", firstName: "", lastName: "", address: "", city: "", province: "", postalCode: "", country: "Thailand" };

function errorMessage(err: any, fallback: string): string {
  if (err && typeof err.getFirstError === "function") return err.getFirstError();
  return err?.message || fallback;
}

// Map the server cart into the UI cart shape.
function toCartItems(items: ApiCartItem[]): CartItem[] {
  return items.map((it) => ({
    product: {
      id: it.product_id,
      name: it.name,
      brand: it.brand,
      price: it.price,
      category: "",
      gender: "",
      rating: 0,
      reviews: 0,
      image: it.image,
      colors: [it.color],
      sizes: [it.size],
    },
    qty: it.quantity,
    color: it.color,
    size: it.size,
  }));
}

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
  // Wishlist, reviews and member info always come from the server.
  const [wishlist, setWishlist] = useState<number[]>([]);
  // Guests keep their bag in localStorage; members use the server cart.
  const [cart, setCart] = useStoredState<CartItem[]>("naemak:cart", []);
  const [reviews, setReviews] = useState<Record<number, ProductReview[]>>({});
  const [loggedIn, setLoggedIn] = useState(false);
  // False until /api/me answers, so member-only pages don't redirect on reload.
  const [authReady, setAuthReady] = useState(false);
  const [query, setQuery] = useState("");
  const [toast, setToast] = useState("");
  const [lastOrder, setLastOrder] = useStoredState<ApiOrder | null>("naemak:last-order", null);
  const [allProducts, setAllProducts] = useState<Product[]>([]);
  const [productsLoading, setProductsLoading] = useState(true);
  const [memberInfo, setMemberInfo] = useState<MemberInfo>(emptyMemberInfo);

  const flash = (s: string) => { setToast(s); window.setTimeout(() => setToast(""), 2400); };

  useEffect(() => {
    if (!loggedIn) return;
    wishlistApi.get().then((res) => {
      if (res && Array.isArray(res.ids)) setWishlist(res.ids);
    }).catch((err) => flash(errorMessage(err, "Could not load your wishlist.")));
    cartApi.get().then((res) => {
      if (res && res.items) setCart(toCartItems(res.items));
    }).catch((err) => flash(errorMessage(err, "Could not load your bag.")));
  }, [loggedIn]);

  useEffect(() => {
    productsApi.list()
      .then((list) => setAllProducts(list || []))
      .catch((err) => flash(errorMessage(err, "Could not load products. Please try again.")))
      .finally(() => setProductsLoading(false));
  }, []);

  useEffect(() => {
    api.get("/me").then((user) => {
      if (user) {
        setLoggedIn(true);
        setMemberInfo(mapUserToMemberInfo(user));
      } else {
        setLoggedIn(false);
      }
    }).catch(() => setLoggedIn(false))
      .finally(() => setAuthReady(true));
  }, []);

  const go = (page: Page) => { navigate(pagePaths[page]); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const view = (product: Product) => { navigate(`/products/${product.id}`); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const resetSession = () => {
    setLoggedIn(false);
    setWishlist([]);
    setCart([]);
    setMemberInfo(emptyMemberInfo);
    setLastOrder(null);
  };
  const toggleWish = (id: number) => {
    if (!loggedIn) { go("login"); return; }
    wishlistApi.toggle(id).then((res) => {
      if (res && Array.isArray(res.ids)) setWishlist(res.ids);
      flash(res?.in_wishlist === false ? "Removed from wishlist" : "Added to wishlist");
    }).catch((err) => flash(errorMessage(err, "Could not update your wishlist.")));
  };
  const add = (p: Product, color = p.colors[0], size = p.sizes[0], quantity = 1) => {
    if (loggedIn) {
      cartApi.add({ product_id: p.id, color, size, quantity }).then((res) => {
        if (res?.cart?.items) setCart(toCartItems(res.cart.items));
        flash(`${p.name} added to bag`);
      }).catch((err) => flash(errorMessage(err, "Could not add this item to your bag.")));
      return;
    }
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
  // Resolves true when the review was saved, so the form only clears on success.
  const submitReview = async (productId: number, review: Pick<ProductReview, "title" | "text" | "rating">): Promise<boolean> => {
    if (!loggedIn) {
      go("login");
      return false;
    }
    try {
      const res = await reviewsApi.submit(productId, {
        title: review.title,
        text: review.text,
        rating: review.rating,
      });
      if (res?.review) {
        const nextReview = res.review;
        setReviews((current) => ({
          ...current,
          [productId]: [nextReview, ...(current[productId] || []).filter((r) => r.id !== nextReview.id)],
        }));
      }
      flash("Review published");
      return true;
    } catch (err: any) {
      flash(errorMessage(err, "Only verified buyers with a completed order can review."));
      return false;
    }
  };
  const filtered = useMemo(() => allProducts.filter((p) => p.name.toLowerCase().includes(query.toLowerCase())), [query, allProducts]);
  const cards = (list: Product[]) => list.map((p) => <ProductCard key={p.id} product={p} wishlisted={wishlist.includes(p.id)} onWish={() => toggleWish(p.id)} onView={() => view(p)} onAdd={() => add(p)}/>);
  const context: ShopContext = {
    add, allProducts, productsLoading, cart, cards, filtered, go, flash, authReady, lastOrder, setLastOrder, loggedIn, memberInfo, query, reviews, setReviews,
    setCart, setLoggedIn, setMemberInfo, setQuery, submitReview, toggleWish, view, wishlist, resetSession,
  };

  return <><Header go={go} cartCount={cart.reduce((s,x)=>s+x.qty,0)} wishCount={wishlist.length} loggedIn={loggedIn} onSearch={(q) => { setQuery(q); go("products"); }}/><Outlet context={context}/>{toast && <div className="toast"><Icon name="check"/>{toast}</div>}</>;
}

function useShop() {
  return useOutletContext<ShopContext>();
}

function Loading({ text = "LOADING…" }: { text?: string }) {
  return <main><section className="section"><p className="eyebrow">{text}</p></section></main>;
}

function HomeRoute() {
  const { cards, filtered, go } = useShop();
  return <HomePage go={go} cards={cards(filtered.slice(0, 4))}/>;
}

function ProductsRoute() {
  const { cards, filtered, productsLoading, query, setQuery } = useShop();
  if (productsLoading) return <Loading text="LOADING PRODUCTS…"/>;
  return <ProductsPage products={filtered} renderCards={cards} query={query} setQuery={setQuery}/>;
}

function ProductDetailRoute() {
  const { productId } = useParams();
  const { add, go, loggedIn, reviews, setReviews, submitReview, toggleWish } = useShop();
  const [productDetail, setProductDetail] = useState<ProductDetail | null>(null);
  const [status, setStatus] = useState<"loading" | "ready" | "missing">("loading");

  useEffect(() => {
    if (!productId) return;
    setStatus("loading");
    productsApi.get(Number(productId)).then((detail) => {
      setProductDetail(detail);
      setStatus(detail ? "ready" : "missing");
      if (detail?.reviews_list) {
        setReviews((cur) => ({ ...cur, [detail.id]: detail.reviews_list }));
      }
    }).catch(() => {
      setProductDetail(null);
      setStatus("missing");
    });
  }, [productId]);

  if (status === "loading") return <Loading/>;
  if (status === "missing" || !productDetail) return <Navigate to="/products" replace/>;
  const product = productDetail;
  const reviewList = reviews[product.id] || [];

  return <ProductDetailPage key={product.id} product={product} onAdd={(color,size,quantity) => add(product,color,size,quantity)} onWish={() => toggleWish(product.id)} go={go} loggedIn={loggedIn} reviews={reviewList} onSubmitReview={(review) => submitReview(product.id, review)}/>;
}

function WishlistRoute() {
  const { add, allProducts, authReady, go, loggedIn, toggleWish, view, wishlist } = useShop();
  if (!authReady) return <Loading/>;
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <WishlistPage products={allProducts.filter((product) => wishlist.includes(product.id))} onRemove={toggleWish} onAdd={(product, size) => add(product, product.colors[0], size)} onView={view} go={go}/>;
}

function CartRoute() {
  const { cart, flash, go, setCart, loggedIn } = useShop();
  const handleRemove = (key: string) => {
    const itemToRemove = cart.find((item) => cartItemKey(item) === key);
    if (!itemToRemove) return;
    if (!loggedIn) {
      setCart((items) => items.filter((item) => cartItemKey(item) !== key));
      return;
    }
    cartApi.remove(0, { product_id: itemToRemove.product.id, color: itemToRemove.color, size: itemToRemove.size })
      .then((res) => { if (res?.cart?.items) setCart(toCartItems(res.cart.items)); })
      .catch((err) => flash(errorMessage(err, "Could not remove this item.")));
  };
  const handleUpdate = (key: string, difference: number) => {
    const itemToUpdate = cart.find((item) => cartItemKey(item) === key);
    if (!itemToUpdate) return;
    const newQty = Math.max(1, itemToUpdate.qty + difference);
    if (!loggedIn) {
      setCart((items) => items.map((item) => cartItemKey(item) === key ? {...item, qty: newQty} : item));
      return;
    }
    cartApi.update(0, newQty, { product_id: itemToUpdate.product.id, color: itemToUpdate.color, size: itemToUpdate.size })
      .then((res) => { if (res?.cart?.items) setCart(toCartItems(res.cart.items)); })
      .catch((err) => flash(errorMessage(err, "Could not update the quantity.")));
  };
  return <CartPage items={cart} go={go} remove={handleRemove} updateQty={handleUpdate}/>;
}

function CheckoutRoute() {
  const { authReady, cart, go, loggedIn, memberInfo, setMemberInfo, setCart, setLastOrder } = useShop();
  // Set once the order is placed, so emptying the cart doesn't bounce us to /cart
  // before we land on the order complete page.
  const [placed, setPlaced] = useState(false);
  if (!authReady) return <Loading/>;
  if (!cart.length && !placed) return <Navigate to="/cart" replace/>;
  // Orders are stored per member on the server, so checkout requires an account.
  if (!loggedIn) return <Navigate to="/login" replace/>;

  // Errors propagate to the checkout page, which shows them above the form.
  const handlePlaceOrder = async (info: MemberInfo, payment: string, saveAddress: boolean) => {
    const res = await ordersApi.checkout({
      firstName: info.firstName,
      lastName: info.lastName,
      email: info.email,
      phone: info.phone,
      address: info.address,
      city: info.city,
      province: info.province,
      postalCode: info.postalCode,
      country: info.country,
      saveAddress,
      payment_method: payment,
    });
    setLastOrder(res.order);
    if (saveAddress) setMemberInfo(info);
    setPlaced(true);
    go("confirmed");
    setCart([]);
  };

  return <CheckoutPage items={cart} go={go} loggedIn={loggedIn} memberInfo={memberInfo} onSaveInfo={setMemberInfo} onPlaceOrder={handlePlaceOrder}/>;
}

// Order complete page shown right after checkout.
function ConfirmationRoute() {
  const { go, lastOrder } = useShop();
  if (!lastOrder) return <Navigate to="/" replace/>;
  const recipientName = (lastOrder.ship_name || "").toUpperCase();
  const itemCount = lastOrder.items.reduce((sum, item) => sum + item.quantity, 0);

  return <main className="order-complete">
    <section className="confirmation"><i><Icon name="check" size={36}/></i><span className="eyebrow">THANK YOU{recipientName ? `, ${recipientName}` : ""}</span><h1>ORDER<br/>COMPLETE.</h1><p>Your order <b>#{lastOrder.order_number}</b> has been placed and completed.<br/>You can review the items you bought right away.</p><div><span>ORDER DATE <b>{lastOrder.formatted_date}</b></span><span>ITEMS <b>{itemCount}</b></span><span>TOTAL <b>฿{Number(lastOrder.total_amount).toLocaleString()}</b></span><span>STATUS <b>{lastOrder.status.toUpperCase()}</b></span></div><nav className="confirmation-actions"><Button onClick={() => go("orders")}>VIEW MY ORDERS</Button><Button variant="outline" onClick={() => go("products")}>CONTINUE SHOPPING</Button></nav></section>
  </main>;
}

function LoginRoute() {
  const { flash, go, setLoggedIn, setMemberInfo } = useShop();
  return <LoginPage go={go} onSuccess={async () => {
    try {
      const user = await authApi.me();
      if (user) {
        setLoggedIn(true);
        setMemberInfo(mapUserToMemberInfo(user));
      }
    } catch (err) {
      flash(errorMessage(err, "Could not load your account."));
    }
    go("home");
  }}/>;
}

function RegisterRoute() {
  const { flash, go, setLoggedIn, setMemberInfo } = useShop();
  return <RegisterPage go={go} onSuccess={async () => {
    try {
      const user = await authApi.me();
      if (user) {
        setLoggedIn(true);
        setMemberInfo(mapUserToMemberInfo(user));
      }
    } catch (err) {
      flash(errorMessage(err, "Could not load your account."));
    }
    go("home");
  }}/>;
}

function AccountRoute() {
  const { authReady, flash, go, loggedIn, memberInfo, resetSession, setMemberInfo } = useShop();
  const navigate = useNavigate();
  if (!authReady) return <Loading/>;
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <AccountPage memberInfo={memberInfo} go={go} onViewProduct={(id) => { navigate(`/products/${id}`); window.scrollTo({ top: 0 }); }} onSaveInfo={async (info) => {
    try {
      const res = await authApi.updateProfile({
        first_name: info.firstName,
        last_name: info.lastName,
        email: info.email,
        phone: info.phone,
        address: info.address,
        city: info.city,
        province: info.province,
        postal_code: info.postalCode,
        country: info.country,
      });
      if (res && res.user) {
        setMemberInfo(mapUserToMemberInfo(res.user));
      }
      flash("Profile saved");
    } catch (err) {
      flash(errorMessage(err, "Could not save your profile."));
    }
  }} onLogout={async () => {
    try {
      await authApi.logout();
    } catch (err) {
      flash(errorMessage(err, "Logout failed."));
      return;
    }
    resetSession();
    go("home");
  }}/>;
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
// No trailing slash, so both "/shop" and "/shop/" match.
], { basename: __ROUTER_BASE__.replace(/\/+$/, "") || "/" });

export default function App() {
  return <RouterProvider router={router}/>;
}
