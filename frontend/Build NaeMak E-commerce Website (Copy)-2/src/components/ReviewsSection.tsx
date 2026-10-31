import { FormEvent, useEffect, useState } from "react";
import { reviewsApi } from "../api/reviews";
import type { Product, ProductReview } from "../types/shop";
import { Button, SectionTitle, Stars } from "./ui";

export default function ReviewsSection({ product, reviews, loggedIn, onLogin, onOrders, onSubmit }: { product: Product; reviews: ProductReview[]; loggedIn: boolean; onLogin: () => void; onOrders: () => void; onSubmit: (review: Pick<ProductReview, "title" | "text" | "rating">) => Promise<boolean> }) {
  const [title, setTitle] = useState("");
  const [text, setText] = useState("");
  const [rating, setRating] = useState(5);
  const [submitting, setSubmitting] = useState(false);
  // Only members with a completed order containing this product may review (checked by the API too).
  const [canReview, setCanReview] = useState<boolean | null>(null);

  useEffect(() => {
    if (!loggedIn) { setCanReview(null); return; }
    let active = true;
    setCanReview(null);
    reviewsApi.eligibility(product.id)
      .then((res) => { if (active) setCanReview(!!res?.can_review); })
      .catch(() => { if (active) setCanReview(false); });
    return () => { active = false; };
  }, [product.id, loggedIn]);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!text.trim() || submitting) return;
    setSubmitting(true);
    const ok = await onSubmit({ title: title.trim() || undefined, text: text.trim(), rating });
    setSubmitting(false);
    // Keep what the member typed if the server rejected it.
    if (!ok) return;
    setTitle("");
    setText("");
    setRating(5);
  };

  let writeReview;
  if (!loggedIn) {
    writeReview = <><strong>WRITE A REVIEW</strong><p>Please log in to write a review.</p><Button variant="outline" onClick={onLogin}>LOG IN</Button></>;
  } else if (canReview === null) {
    writeReview = <><strong>WRITE A REVIEW</strong><p>Checking your orders…</p></>;
  } else if (!canReview) {
    writeReview = <><strong>WRITE A REVIEW</strong><p>Reviews are open to verified buyers. You can review this item once an order containing it has been delivered — press “Mark as received” in your order history when it arrives.</p><Button variant="outline" onClick={onOrders}>VIEW MY ORDERS</Button></>;
  } else {
    writeReview = <form onSubmit={submit}><strong>WRITE A REVIEW</strong><label><span>RATING</span><span className="review-stars" aria-label={`${rating} out of 5 stars`}>{[1,2,3,4,5].map((value) => <button type="button" key={value} className={value <= rating ? "active" : ""} onClick={() => setRating(value)} aria-label={`${value} stars`} aria-pressed={value === rating}>★</button>)}</span></label><label><span>REVIEW TITLE <small>OPTIONAL</small></span><input value={title} onChange={(event) => setTitle(event.target.value)} placeholder="Summarize your review"/></label><label><span>REVIEW</span><textarea required value={text} onChange={(event) => setText(event.target.value)} placeholder="Share your experience"/></label><Button type="submit" disabled={!text.trim() || submitting}>{submitting ? "SUBMITTING…" : "SUBMIT REVIEW"}</Button></form>;
  }

  return <section className="reviews-section">
    <SectionTitle kicker="COMMUNITY" title="CUSTOMER REVIEWS"/>
    <div className="review-summary">
      {/* product.rating / product.reviews are the server's average score and number of ratings */}
      <div className="score"><strong>{product.rating.toFixed(1)}</strong><Stars value={product.rating}/><span>BASED ON {product.reviews} RATINGS</span></div>
      <div className="review-overview"><strong>WHAT MEMBERS SAY</strong><p>Considered fits, premium fabrics and everyday pieces reviewed by the NAE MAK community.</p></div>
      <div className="write-review">{writeReview}</div>
    </div>
    <div className="review-list">{reviews.length ? reviews.map((review) => <article key={review.id}><div><strong>{review.reviewer}</strong><Stars value={review.rating}/></div>{review.title && <h3>{review.title}</h3>}<p>“{review.text}”</p><span>{review.date}</span></article>) : <p className="no-reviews">No written reviews yet. Be the first to share your experience.</p>}</div>
  </section>;
}
