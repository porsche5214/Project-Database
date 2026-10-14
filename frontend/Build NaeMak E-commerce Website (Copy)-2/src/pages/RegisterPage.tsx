import type { Page } from "../types/shop";
import { Auth } from "./storefront";

type RegisterPageProps = {
  go: (page: Page) => void;
  onSuccess: () => void;
};

export default function RegisterPage(props: RegisterPageProps) {
  return <Auth mode="register" {...props} />;
}
