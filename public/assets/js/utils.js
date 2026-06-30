export function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

export function sanitizeHtml(html) {
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, "text/html");
  for (const tag of ["script", "style", "iframe", "object", "embed"]) {
    for (const el of doc.body.querySelectorAll(tag)) {
      el.remove();
    }
  }
  return doc.body.innerHTML;
}

export function formatCPF(cpf) {
  if (!cpf) return "";
  const digits = cpf.replace(/\D/g, "");
  return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
}
