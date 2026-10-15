import { productsApi, type ProductDetail } from "./api/products";
import { cartApi } from "./api/cart";
import { wishlistApi } from "./api/wishlist";
import { ordersApi, type ApiOrder } from "./api/orders";
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
import { products as initialMockProducts } from "./data/products";
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
  allProducts: Product[];
  lastOrder: ApiOrder | null;
  setLastOrder: React.Dispatch<React.SetStateAction<ApiOrder | null>>;
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
  const [lastOrder, setLastOrder] = useStoredState<ApiOrder | null>("naemak:last-order", null);
  const [allProducts, setAllProducts] = useState<Product[]>(initialMockProducts);
  useEffect(() => {
    if (loggedIn) {
      wishlistApi.get().then((res) => {
        if (res && Array.isArray(res.ids)) {
          setWishlist(res.ids);
        }
      }).catch(() => {});
    }
  }, [loggedIn]);

  useEffect(() => {
    if (loggedIn) {
      cartApi.get().then((res) => {
        if (res && res.items) {
          const apiItems: CartItem[] = res.items.map((it) => ({
            product: {
              id: it.product_id,
              name: it.name,
              brand: it.brand,
              price: it.price,
              category: "",
              gender: "",
              rating: 5,
              reviews: 0,
              image: it.image,
              colors: [it.color],
              sizes: [it.size],
            },
            qty: it.quantity,
            color: it.color,
            size: it.size,
          }));
          setCart(apiItems);
        }
      }).catch(() => {});
    }
  }, [loggedIn]);


  useEffect(() => {
    productsApi.list().then((list) => {
      if (list && list.length) {
        setAllProducts(list);
      }
    }).catch(() => {});
  }, []);
  const [memberInfo, setMemberInfo] = useStoredState<MemberInfo>("naemak:member", { email:"araya@naemak.com", phone:"+66 81 234 5678", firstName:"Araya", lastName:"Siriporn", address:"88 Sukhumvit Road, Khlong Toei", city:"Bangkok", province:"Bangkok", postalCode:"10110", country:"Thailand" });
  useEffect(() => {
    api.get('/me').then((user) => {
      if (user) {
        setLoggedIn(true);
        setMemberInfo({
          email: user.email || "",
          phone: user.phone || "",
          firstName: user.first_name || "",
          lastName: user.last_name || "",
          address: user.default_address?.address_line || "",
          city: user.default_address?.district || "",
          province: user.default_address?.province || "",
          postalCode: user.default_address?.postal_code || "",
          country: "Thailand",
        });
      } else {
        setLoggedIn(false);
      }
    }).catch(() => {});
  }, []);
  const go = (page: Page) => { navigate(pagePaths[page]); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const flash = (s: string) => { setToast(s); window.setTimeout(() => setToast(""), 2400); };
  const view = (product: Product) => { navigate(`/products/${product.id}`); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const toggleWish = (id: number) => {
    if (!loggedIn) { go("login"); return; }
    setWishlist((w) => w.includes(id) ? w.filter((x) => x !== id) : [...w, id]);
    flash("Wishlist updated");
    wishlistApi.toggle(id).then((res) => {
      if (res && Array.isArray(res.ids)) {
        setWishlist(res.ids);
      }
    }).catch(() => {});
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
    if (loggedIn) {
      cartApi.add({ product_id: p.id, color, size, quantity }).catch(() => {});
    }
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
  const filtered = useMemo(() => allProducts.filter((p) => p.name.toLowerCase().includes(query.toLowerCase())), [query, allProducts]);
  const cards = (list: Product[]) => list.map((p) => <ProductCard key={p.id} product={p} wishlisted={wishlist.includes(p.id)} onWish={() => toggleWish(p.id)} onView={() => view(p)} onAdd={() => add(p)}/>);
  const context: ShopContext = {
    add, allProducts, cart, cards, filtered, go, lastOrder, setLastOrder, loggedIn, memberInfo, query, reviews, setCart, setLoggedIn,
    setMemberInfo, setQuery, submitReview, toggleWish, view, wishlist,
  };

  return <><Header go={go} cartCount={cart.reduce((s,x)=>s+x.qty,0)} wishCount={wishlist.length} loggedIn={loggedIn} onSearch={(q) => { setQuery(q); go("products"); }}/><Outlet context={context}/>{toast && <div className="toast"><Icon name="check"/>{toast}</div>}</>;
}

function useShop() {
  return useOutletContext<ShopContext>();
}

function HomeRoute() {
  const { cards, filtered, go } = useShop();
  return <HomePage go={go} cards={cards(filtered.slice(0, 4))}/>;
}

function ProductsRoute() {
  const { cards, filtered, query, setQuery } = useShop();
  return <ProductsPage products={filtered} renderCards={cards} query={query} setQuery={setQuery}/>;
}

function ProductDetailRoute() {
  const { productId } = useParams();
  const { add, go, loggedIn, reviews, submitReview, toggleWish } = useShop();
  const [productDetail, setProductDetail] = useState<ProductDetail | null>(null);

  useEffect(() => {
    if (!productId) return;
    productsApi.get(Number(productId)).then((detail) => {
      setProductDetail(detail);
    }).catch(() => {
      setProductDetail(null);
    });
  }, [productId]);

  const fallbackProduct = initialMockProducts.find((item) => item.id === Number(productId));
  const product = productDetail || fallbackProduct;
  if (!product) return <Navigate to="/products" replace/>;
  const reviewList = (productDetail?.reviews_list && productDetail.reviews_list.length)
    ? productDetail.reviews_list
    : (reviews[product.id] || []);

  return <ProductDetailPage product={product} onAdd={(color,size,quantity) => add(product,color,size,quantity)} onWish={() => toggleWish(product.id)} go={go} loggedIn={loggedIn} reviews={reviewList} onSubmitReview={(review) => submitReview(product.id, review)}/>;
}

function WishlistRoute() {
  const { add, allProducts, go, loggedIn, toggleWish, view, wishlist } = useShop();
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <WishlistPage products={allProducts.filter((product) => wishlist.includes(product.id))} onRemove={toggleWish} onAdd={(product, size) => add(product, product.colors[0], size)} onView={view} go={go}/>;
}

function CartRoute() {
  const { cart, go, setCart, loggedIn } = useShop();
  const handleRemove = (key: string) => {
    const itemToRemove = cart.find((item) => cartItemKey(item) === key);
    setCart((items) => items.filter((item) => cartItemKey(item) !== key));
    if (loggedIn && itemToRemove) {
      cartApi.remove(0, { product_id: itemToRemove.product.id, color: itemToRemove.color, size: itemToRemove.size }).catch(() => {});
    }
  };
  const handleUpdate = (key: string, difference: number) => {
    const itemToUpdate = cart.find((item) => cartItemKey(item) === key);
    const newQty = Math.max(1, (itemToUpdate?.qty || 1) + difference);
    setCart((items) => items.map((item) => cartItemKey(item) === key ? {...item, qty: newQty} : item));
    if (loggedIn && itemToUpdate) {
      cartApi.update(0, newQty, { product_id: itemToUpdate.product.id, color: itemToUpdate.color, size: itemToUpdate.size }).catch(() => {});
    }
  };
  return <CartPage items={cart} go={go} remove={handleRemove} updateQty={handleUpdate}/>;
}

function CheckoutRoute() {
  const { cart, go, loggedIn, memberInfo, setMemberInfo, setCart, setLastOrder } = useShop();
  if (!cart.length) return <Navigate to="/cart" replace/>;

  const handlePlaceOrder = async (info: MemberInfo, payment: string, saveAddress: boolean) => {
    if (loggedIn) {
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
      if (res && res.order) {
        setLastOrder(res.order);
      }
      setCart([]);
      go("confirmed");
    } else {
      if (saveAddress) setMemberInfo(info);
      setCart([]);
      go("confirmed");
    }
  };

  return <CheckoutPage items={cart} go={go} loggedIn={loggedIn} memberInfo={memberInfo} onSaveInfo={setMemberInfo} onPlaceOrder={handlePlaceOrder}/>;
}

function ConfirmationRoute() {
  const { cart, go, lastOrder, memberInfo } = useShop();
  const orderNum = lastOrder ? `#${lastOrder.order_number}` : "#NM-250621-0914";
  const orderDate = lastOrder ? lastOrder.formatted_date : "21 JUN 2025";
  const orderTotal = lastOrder ? lastOrder.total_amount : cart.reduce((sum,item)=>sum+item.product.price*item.qty,0);
  const orderStatus = lastOrder ? lastOrder.status.toUpperCase() : "PAID";
  const recipientName = lastOrder?.ship_name ? lastOrder.ship_name.toUpperCase() : (memberInfo.firstName ? memberInfo.firstName.toUpperCase() : "ARAYA");

  return <main><section className="confirmation"><i><Icon name="check" size={36}/></i><span className="eyebrow">THANK YOU, {recipientName}</span><h1>ORDER PLACED<br/>SUCCESSFULLY.</h1><p>Your order <b>{orderNum}</b> is confirmed.<br/>We’ll let you know when it’s on its way.</p><div><span>ORDER DATE <b>{orderDate}</b></span><span>TOTAL <b>฿{Number(orderTotal).toLocaleString()}</b></span><span>STATUS <b>{orderStatus}</b></span></div><Button onClick={() => go("account")}>VIEW MY ORDERS</Button></section></main>;
}

function LoginRoute() {
  const { go, setLoggedIn, setMemberInfo } = useShop();
  return <LoginPage go={go} onSuccess={async () => {
    try {
      const user = await authApi.me();
      if (user) {
        setLoggedIn(true);
        setMemberInfo(mapUserToMemberInfo(user));
      }
    } catch {}
    go("home");
  }}/>;
}

function RegisterRoute() {
  const { go, setLoggedIn, setMemberInfo } = useShop();
  return <RegisterPage go={go} onSuccess={async () => {
    try {
      const user = await authApi.me();
      if (user) {
        setLoggedIn(true);
        setMemberInfo(mapUserToMemberInfo(user));
      }
    } catch {}
    go("home");
  }}/>;
}

function AccountRoute() {
  const { go, loggedIn, memberInfo, setLoggedIn, setMemberInfo } = useShop();
  if (!loggedIn) return <Navigate to="/login" replace/>;
  return <AccountPage memberInfo={memberInfo} go={go} onSaveInfo={async (info) => {
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
    } catch (e) {
      console.error(e);
    }
  }} onLogout={async () => {
    try {
      await authApi.logout();
    } catch {}
    setLoggedIn(false);
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
], { basename: import.meta.env.BASE_URL });

export default function App() {
  return <RouterProvider router={router}/>;
}
