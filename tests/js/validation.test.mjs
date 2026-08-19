import { test } from "node:test";
import assert from "node:assert/strict";
import { validarCPF, validarPlaca } from "../../public/assets/js/validation.js";

function campoMock(valor) {
  return {
    value: valor,
    classes: new Set(),
    classList: {
      add: (c) => campo.classes.add(c),
      remove: (c) => campo.classes.delete(c),
    },
  };
}

let campo;

globalThis.document = {
  getElementById: () => ({ textContent: "" }),
};

test("validarCPF aceita CPF válido", () => {
  campo = campoMock("529.982.247-25");
  const ok = validarCPF(campo);
  assert.equal(ok, true);
  assert.equal(campo.classes.has("is-invalid"), false);
  assert.equal(campo.classes.has("is-valid"), true);
});

test("validarCPF rejeita dígitos repetidos", () => {
  campo = campoMock("111.111.111-11");
  const ok = validarCPF(campo);
  assert.equal(ok, false);
  assert.equal(campo.classes.has("is-invalid"), true);
});

test("validarCPF rejeita quantidade errada de dígitos", () => {
  campo = campoMock("123");
  assert.equal(validarCPF(campo), false);
});

test("validarCPF rejeita dígitos verificadores inválidos", () => {
  campo = campoMock("123.456.789-00");
  assert.equal(validarCPF(campo), false);
});

test("validarCPF retorna false para campo vazio", () => {
  assert.equal(validarCPF(null), false);
});

test("validarPlaca aceita formato antigo ABC-1234", () => {
  campo = campoMock("abc-1234");
  const ok = validarPlaca(campo);
  assert.equal(ok, true);
  assert.equal(campo.classes.has("is-invalid"), false);
});

test("validarPlaca aceita formato Mercosul ABC1D23", () => {
  campo = campoMock("ABC1D23");
  assert.equal(validarPlaca(campo), true);
});

test("validarPlaca rejeita placa inválida", () => {
  campo = campoMock("ABC-12");
  assert.equal(validarPlaca(campo), false);
  assert.equal(campo.classes.has("is-invalid"), true);
});

test("validarPlaca aceita campo vazio", () => {
  campo = campoMock("");
  assert.equal(validarPlaca(campo), true);
});