<?php
namespace PrestaShop\PrestaShop\Core\Payment;

if (!class_exists('PrestaShop\PrestaShop\Core\Payment\PaymentOption')) {
    class PaymentOption
    {
        public function setCallToActionText(string $text): static { return $this; }
        public function setAction(string $url): static { return $this; }
        public function setForm(?string $form): static { return $this; }
        public function setModuleName(string $name): static { return $this; }
    }
}