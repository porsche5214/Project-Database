import AuthForm from "../components/AuthForm";
import type { Page } from "../types/shop";

type RegisterPageProps = {
  go: (page: Page) => void;
  onSuccess: () => void;
};

export default function RegisterPage(props: RegisterPageProps) {
  return <AuthForm mode="register" {...props} />;
}
