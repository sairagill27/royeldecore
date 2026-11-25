<?php

namespace App\Console\Commands;

use App\Models\AssistantAnswer;
use Illuminate\Console\Command;

class AssistantSeedAnswers extends Command
{
    protected $signature = 'assistant:seed-answers';

    protected $description = 'Adds a starter set of saved answers for the Decor Assistant (it never overwrites yours)';

    public function handle(): int
    {
        $cur  = (string) config('shop.currency', 'Rs.');
        $fee  = (int) config('shop.shipping_fee', 0);
        $over = (int) config('shop.free_shipping_over', 0);

        $delivery = $fee > 0
            ? 'The delivery charge is ' . $cur . ' ' . number_format($fee) . ($over > 0 ? ', and there is no delivery charge on orders over ' . $cur . ' ' . number_format($over) : '') . '.'
            : 'The delivery charge is shown at checkout.';

        // Only details that are really set
        $parts = [];
        $wa    = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
        if (strlen($wa) >= 10 && strlen($wa) <= 15) {
            $parts[] = 'WhatsApp +' . $wa;
        }
        $email = (string) config('contact.email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $parts[] = 'email ' . $email;
        }
        $contact = ($parts ? 'You can reach us on ' . implode(' or ', $parts) . ', or ' : 'You can ') . 'use the contact form on the Contact page.';

        $answers = [
            [
                'How does cash on delivery work?',
                'cash on delivery, cod, pay on delivery, pay when, payment method, payment methods, how to pay',
                'You pay in cash when your order arrives. There is no online card payment. ' . $delivery,
            ],
            [
                'How do I place an order?',
                'how to order, how do i order, place an order, how can i buy, how do i buy, how to buy',
                "Add the pieces you like to your cart, sign in or create a free account, and enter your delivery details at checkout.\nYou pay by cash on delivery, when your order arrives.",
            ],
            [
                'How can I track my order?',
                'track my order, track order, tracking, order status, where is my order, my orders',
                'After you sign in, open My Orders to see the status of each of your orders. You also get an order confirmation by email.',
            ],
            [
                'What is the delivery charge?',
                'delivery charge, delivery fee, delivery cost, shipping charge, shipping fee, shipping cost, free delivery',
                $delivery,
            ],
            [
                'What free tools do you have?',
                'free tools, calculator, calculators, paint calculator, flooring calculator, wallpaper calculator, curtain calculator, budget planner, decor tools, décor tools',
                "We have free calculators for paint, flooring, wallpaper and curtains, and a room budget planner. The Décor Helper also suggests pieces by room, style and budget.\nOpen Tools in the menu. The results are estimates to help you plan.",
            ],
            [
                'How can I contact the shop?',
                'contact, contact us, contact you, phone number, whatsapp number, email address, how can i reach, talk to someone, speak to someone',
                $contact,
            ],
        ];

        $added = 0;
        foreach ($answers as [$question, $keywords, $answer]) {
            $item = AssistantAnswer::firstOrCreate(
                ['question' => $question],
                ['keywords' => $keywords, 'answer' => $answer, 'is_active' => true]
            );

            if ($item->wasRecentlyCreated) {
                $added++;
            }
        }

        $this->info($added . ' answer(s) added, ' . (count($answers) - $added) . ' already existed.');
        $this->line('Read them in the admin panel (Assistant answers) and edit anything that is not right for your shop.');

        return self::SUCCESS;
    }
}