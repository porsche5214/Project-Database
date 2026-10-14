import { FormEvent, useMemo, useState } from "react";
import type { Product, ProductReview } from "../types/shop";
import { Button, SectionTitle, Stars } from "./ui";

export default function ReviewsSection({ product, reviews, loggedIn, onLogin, onSubmit }: { product: Product; reviews: ProductReview[]; loggedIn: boolean; onLogin: () => void; onSubmit: (review: Pick<ProductReview, "title" | "text" | "rating">) => void }) {
  const [title, setTitle] = useState("");
  const [text, setText] = useState("");
  const [rating, setRating] = useState(5);
  const totalReviews = product.reviews + reviews.length;
  const averageRating = useMemo(() => {
    if (!reviews.length) return product.rating;
    return (product.rating * product.reviews + reviews.reduce((sum, review) => sum + review.rating, 0)) / totalReviews;
  }, [product.rating, product.reviews, reviews, totalReviews]);
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!text.trim()) return;
    onSubmit({ title: title.trim() || undefined, text: text.trim(), rating });
    setTitle("");
    setText("");
    setRating(5);
  };

  return <section className="reviews-section">
    <SectionTitle kicker="COMMUNITY" title="CUSTOMER REVIEWS"/>
    <div className="review-summary">
      <div className="score"><strong>{averageRating.toFixed(1)}</strong><Stars value={averageRating}/><span>BASED ON {totalReviews} REVIEWS</span></div>
      <div className="review-overview"><strong>WHAT MEMBERS SAY</strong><p>Considered fits, premium fabrics and everyday pieces reviewed by the NAE MAK community.</p></div>
      <div className="write-review">
        {!loggedIn ? <><strong>WRITE A REVIEW</strong><p>Please log in to write a review.</p><Button variant="outline" onClick={onLogin}>LOG IN</Button></> :
          <form onSubmit={submit}><strong>WRITE A REVIEW</strong><label><span>RATING</span><span className="review-stars" aria-label={`${rating} out of 5 stars`}>{[1,2,3,4,5].map((value) => <button type="button" key={value} className={value <= rating ? "active" : ""} onClick={() => setRating(value)} aria-label={`${value} stars`} aria-pressed={value === rating}>★</button>)}</span></label><label><span>REVIEW TITLE <small>OPTIONAL</small></span><input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="Summarize your review"/></label><label><span>REVIEW</span><textarea required value={text} onChange={(event) => setText(event.target.value)} placeholder="Share your experience"/></label><Button type="submit" disabled={!text.trim()}>SUBMIT REVIEW</Button></form>}
      </div>
    </div>
    <div className="review-list">{reviews.length ? reviews.map((review) => <article key={review.id}><div><strong>{review.reviewer}</strong><Stars value={review.rating}/></div>{review.title && <h3>{review.title}</h3>}<p>“{review.text}”</p><span>{review.date}</span></article>) : <p className="no-reviews">No written reviews yet. Be the first to share your experience.</p>}</div>
  </section>;
}
