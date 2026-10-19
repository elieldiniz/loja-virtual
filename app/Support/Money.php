<?php

namespace App\Support;

use InvalidArgumentException;
use Stringable;

/** Valor monetário em centavos inteiros (sem float, sem bcmath). */
final class Money implements Stringable
{
    private function __construct(private readonly int $cents) {}

    public static function cents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** @param  int|float|string|null  $decimal  valor em reais, ex.: "99.90" */
    public static function from(int|float|string|null $decimal): self
    {
        if ($decimal === null || $decimal === '') {
            return self::zero();
        }

        if (is_string($decimal) && ! preg_match('/^-?\d+(\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Valor monetário inválido: {$decimal}");
        }

        return new self((int) round(((float) $decimal) * 100));
    }

    public function amount(): int
    {
        return $this->cents;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function times(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    /** Divide por n arredondando para baixo no centavo. */
    public function divideFloor(int $parts): self
    {
        return new self(intdiv($this->cents, $parts));
    }

    /** Divide por n arredondando meio para cima no centavo. */
    public function divideRound(int $parts): self
    {
        return new self(intdiv($this->cents * 2 + $parts, $parts * 2));
    }

    /** Aplica um percentual (ex.: 95 = 95%) arredondando meio para cima no centavo. */
    public function percent(int $percent): self
    {
        return new self(intdiv($this->cents * $percent * 2 + 100, 200));
    }

    public function min(self $other): self
    {
        return $this->cents <= $other->cents ? $this : $other;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    /** Representação decimal para o banco, ex.: "99.90". */
    public function toDecimal(): string
    {
        $sign = $this->cents < 0 ? '-' : '';
        $abs = abs($this->cents);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }

    /** Formato brasileiro, ex.: "R$ 1.234,56". */
    public function format(): string
    {
        $sign = $this->cents < 0 ? '-' : '';
        $abs = abs($this->cents);

        return sprintf('%sR$ %s,%02d', $sign, number_format(intdiv($abs, 100), 0, ',', '.'), $abs % 100);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
