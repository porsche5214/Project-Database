import AuthForm from "../components/AuthForm";
import type { Page } from "../types/shop";

type LoginPageProps = {
  go: (page: Page) => void;
  onSuccess: () => void;
};

export default function LoginPage(props: LoginPageProps) {
  return <AuthForm mode="login" {...props} />;
}
