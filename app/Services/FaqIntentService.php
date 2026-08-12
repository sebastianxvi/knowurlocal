<?php

namespace App\Services;

class FaqIntentService
{
    /**
     * Detect the primary intent of a user's FAQ question.
     *
     * This is intentionally rule-based.
     *
     * The purpose is NOT to understand the entire question.
     * The purpose is to identify what kind of information
     * the user is requesting.
     */
    public function detect(string $question): string
    {
        /*
         * Normalize the question before checking patterns.
         *
         * Lowercase makes matching case-insensitive.
         * Removing extra whitespace makes the checks more
         * consistent when users type unusual spacing.
         */
        $text = mb_strtolower(
            trim(
                preg_replace('/\s+/', ' ', $question)
            ),
            'UTF-8'
        );

        /*
         * Requirements:
         *
         * Questions asking what documents, information,
         * materials, or prerequisites are needed.
         */
        if ($this->matches($text, [
            'requirement',
            'requirements',
            'required',
            'document',
            'documents',
            'what do i need',
            'what do i need to bring',
            'what should i bring',
            'what papers',
            'needed',
            'kailangan',
            'mga kailangan',
            'ano ang kailangan',
            'ano kailangan',
            'anong kailangan',
            'anong mga dokumento',
            'mga dokumento',
            'dalhin',
        ])) {
            return 'requirements';
        }

        /*
         * Procedure:
         *
         * Questions asking how to perform or complete
         * a service or application.
         */
        if ($this->matches($text, [
            'how do i apply',
            'how can i apply',
            'how to apply',
            'how do i request',
            'how can i request',
            'how to request',
            'how do i get',
            'how can i get',
            'how to get',
            'how do i obtain',
            'how can i obtain',
            'what are the steps',
            'steps to apply',
            'application process',
            'apply for',
            'request for',
            'paano mag-apply',
            'paano mag apply',
            'paano kumuha',
            'paano makakuha',
            'paano mag-request',
            'paano mag request',
            'paano humingi',
            'paano magparehistro',
            'paano mag-register',
            'ano ang proseso',
            'anong proseso',
            'mga hakbang',
        ])) {
            return 'procedure';
        }

        /*
         * Eligibility:
         *
         * Questions asking who is qualified or allowed
         * to receive a service.
         */
        if ($this->matches($text, [
            'who can apply',
            'who is eligible',
            'am i eligible',
            'eligible',
            'eligibility',
            'qualified',
            'qualification',
            'who qualifies',
            'sino ang maaaring',
            'sino ang pwede',
            'sino ang puwede',
            'sino ang kwalipikado',
            'kwalipikado',
            'maaari ba akong',
            'pwede ba akong',
            'puwede ba akong',
        ])) {
            return 'eligibility';
        }

        /*
         * Fees:
         *
         * Questions asking about cost, payment, or charges.
         */
        if ($this->matches($text, [
            'how much',
            'how much does',
            'how much is',
            'fee',
            'fees',
            'cost',
            'price',
            'charge',
            'charges',
            'payment',
            'pay',
            'magkano',
            'bayad',
            'bayaran',
            'may bayad',
            'magkano ang',
        ])) {
            return 'fees';
        }

        /*
         * Processing time:
         *
         * Questions asking how long the service takes
         * or when a requested document/service becomes available.
         */
        if ($this->matches($text, [
            'how long',
            'how long does it take',
            'processing time',
            'how many days',
            'when will it be ready',
            'when can i claim',
            'when can i get',
            'gaano katagal',
            'ilang araw',
            'kailan makukuha',
            'kailan ko makukuha',
            'oras ng processing',
            'tagal ng processing',
        ])) {
            return 'processing_time';
        }

        /*
         * Office hours:
         *
         * Questions about opening, closing, or operating hours.
         */
        if ($this->matches($text, [
            'office hours',
            'office hour',
            'opening hours',
            'closing hours',
            'what time does',
            'what time is',
            'when does the office open',
            'when does the office close',
            'open at',
            'close at',
            'anong oras',
            'anong oras bukas',
            'anong oras nagsasara',
            'oras ng opisina',
            'office schedule',
        ])) {
            return 'office_hours';
        }

        /*
         * Contact:
         *
         * Questions asking for contact channels such as
         * phone numbers, email addresses, or official contact details.
         */
        if ($this->matches($text, [
            'contact number',
            'contact information',
            'contact info',
            'phone number',
            'telephone number',
            'mobile number',
            'email',
            'email address',
            'how can i contact',
            'how do i contact',
            'how to contact',
            'paano makontak',
            'paano kontakin',
            'contact number ng',
            'numero ng telepono',
            'telephone',
        ])) {
            return 'contact';
        }

        /*
         * Location:
         *
         * Questions asking where a specific registered agency
         * or office is located.
         */
        if ($this->matches($text, [
            'where is the office',
            'where is the agency',
            'where can i find the office',
            'where can i find the agency',
            'office location',
            'agency location',
            'address',
            'office address',
            'where is',
            'saan ang opisina',
            'saan matatagpuan',
            'saan makikita',
            'lokasyon',
            'address ng',
        ])) {
            return 'location';
        }

        /*
         * Service:
         *
         * General questions asking what an agency provides
         * or what services/programs are available.
         */
        if ($this->matches($text, [
            'what services',
            'what service',
            'services offered',
            'services do you offer',
            'what does the agency do',
            'what can i get',
            'what assistance',
            'what programs',
            'anong serbisyo',
            'anong mga serbisyo',
            'mga serbisyo',
            'serbisyo ng',
            'anong tulong',
            'anong mga programa',
            'mga programa',
        ])) {
            return 'service';
        }

        /*
         * No recognized intent.
         *
         * "other" does NOT mean the question is outside
         * KNOWURLOCAL's scope.
         *
         * It simply means that this detector could not
         * confidently classify the requested information type.
         */
        return 'other';
    }

    /**
     * Check whether any known phrase appears in the
     * normalized question.
     *
     * Using a helper keeps the detect() method readable
     * and prevents duplicate matching logic.
     */
    private function matches(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }
}