// One group of options in the products page filter panel (checkbox list or chips).
export default function Filter({ title, options, selected, onToggle, chips = false, label = (x) => x }: { title: string; options: string[]; selected: string[]; onToggle: (value: string) => void; chips?: boolean; label?: (value: string) => string }) {
  return <div className="filter-group"><strong>{title}</strong><div className={chips ? "filter-chips" : "filter-options"}>{options.map((x) => chips ? <button key={x} className={selected.includes(x) ? "selected" : ""} onClick={() => onToggle(x)} aria-pressed={selected.includes(x)}>{label(x)}</button> : <label key={x}><input type="checkbox" checked={selected.includes(x)} onChange={() => onToggle(x)}/><span>{label(x)}</span></label>)}</div></div>;
}
