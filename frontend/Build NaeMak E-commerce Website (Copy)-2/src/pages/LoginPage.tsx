import type { Page } from "../types/shop";
import { Auth } from "./storefront";

type LoginPageProps = {
  go: (page: Page) => void;
  onSuccess: () => void;
};

export default function LoginPage(props: LoginPageProps) {
  return <Auth mode="login" {...props} />;
}
