import { test } from "node:test";
import assert from "node:assert/strict";
import { escapeHtml, formatCPF } from "../../public/assets/js/utils.js";

test("escapeHtml escapa caracteres HTML", () => {
  assert.equal(
    escapeHtml(`<script>alert("x")</script>`),
    "&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;"
  );
});

test("escapeHtml escapa aspas simples", () => {
  assert.equal(escapeHtml(`it's`), "it&#39;s");
});

test("escapeHtml retorna string vazia para null/undefined", () => {
  assert.equal(escapeHtml(null), "");
  assert.equal(escapeHtml(undefined), "");
});

test("escapeHtml mantém texto normal", () => {
  assert.equal(escapeHtml("João da Silva"), "João da Silva");
});

test("formatCPF formata 11 dígitos", () => {
  assert.equal(formatCPF("12345678901"), "123.456.789-01");
});

test("formatCPF ignora não-dígitos", () => {
  assert.equal(formatCPF("123.456.789-01"), "123.456.789-01");
});

test("formatCPF retorna vazio para vazio", () => {
  assert.equal(formatCPF(""), "");
  assert.equal(formatCPF(null), "");
});