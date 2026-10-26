/**
 * Return the URL only when it uses http or https, otherwise undefined.
 */
export const safeUrl = (u) =>
  typeof u === "string" && /^https?:\/\//i.test(u.trim()) ? u : undefined;
