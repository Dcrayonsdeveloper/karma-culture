<x-layouts.app>
    <x-slot name="title">Size Guide - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Kids' clothing size guide at {{ config('app.name') }}. Find the perfect fit for boys and girls with our sizing charts.">
        <link rel="canonical" href="{{ url('/size-guide') }}">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Size Guide', 'url' => null]]" />
        </div>
    </div>

    <section class="py-10 sm:py-14">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-10">
                    <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-3">Kids' Clothing Size Guide</h1>
                    <p class="text-sm text-neutral-600 max-w-xl mx-auto">Find the perfect fit for your little one. Use the chart below to match your child's measurements with our size numbers.</p>
                </div>

                <!-- Size Chart -->
                <div class="bg-white rounded-xl border border-neutral-100 overflow-hidden mb-10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-neutral-50">
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700">Size</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700">Age</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700">Height (cm)</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700">Chest (cm)</th>
                                    <th class="px-4 py-3 text-left font-semibold text-neutral-700">Waist (cm)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">18</td><td class="px-4 py-2.5 text-neutral-600">0 – 3 months</td><td class="px-4 py-2.5 text-neutral-600">50 – 56</td><td class="px-4 py-2.5 text-neutral-600">36 – 38</td><td class="px-4 py-2.5 text-neutral-600">36 – 38</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">20</td><td class="px-4 py-2.5 text-neutral-600">3 – 6 months</td><td class="px-4 py-2.5 text-neutral-600">56 – 62</td><td class="px-4 py-2.5 text-neutral-600">38 – 40</td><td class="px-4 py-2.5 text-neutral-600">38 – 40</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">22</td><td class="px-4 py-2.5 text-neutral-600">6 – 9 months</td><td class="px-4 py-2.5 text-neutral-600">62 – 68</td><td class="px-4 py-2.5 text-neutral-600">40 – 42</td><td class="px-4 py-2.5 text-neutral-600">40 – 42</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">24</td><td class="px-4 py-2.5 text-neutral-600">9 – 12 months</td><td class="px-4 py-2.5 text-neutral-600">68 – 74</td><td class="px-4 py-2.5 text-neutral-600">42 – 44</td><td class="px-4 py-2.5 text-neutral-600">42 – 44</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">26</td><td class="px-4 py-2.5 text-neutral-600">1 – 1.5 years</td><td class="px-4 py-2.5 text-neutral-600">74 – 80</td><td class="px-4 py-2.5 text-neutral-600">44 – 46</td><td class="px-4 py-2.5 text-neutral-600">44 – 45</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">28</td><td class="px-4 py-2.5 text-neutral-600">1.5 – 2 years</td><td class="px-4 py-2.5 text-neutral-600">80 – 86</td><td class="px-4 py-2.5 text-neutral-600">46 – 48</td><td class="px-4 py-2.5 text-neutral-600">45 – 47</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">30</td><td class="px-4 py-2.5 text-neutral-600">2 – 3 years</td><td class="px-4 py-2.5 text-neutral-600">86 – 92</td><td class="px-4 py-2.5 text-neutral-600">48 – 50</td><td class="px-4 py-2.5 text-neutral-600">47 – 48</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">32</td><td class="px-4 py-2.5 text-neutral-600">3 – 4 years</td><td class="px-4 py-2.5 text-neutral-600">92 – 98</td><td class="px-4 py-2.5 text-neutral-600">50 – 52</td><td class="px-4 py-2.5 text-neutral-600">48 – 50</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">34</td><td class="px-4 py-2.5 text-neutral-600">4 – 5 years</td><td class="px-4 py-2.5 text-neutral-600">98 – 104</td><td class="px-4 py-2.5 text-neutral-600">52 – 54</td><td class="px-4 py-2.5 text-neutral-600">50 – 52</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">36</td><td class="px-4 py-2.5 text-neutral-600">5 – 6 years</td><td class="px-4 py-2.5 text-neutral-600">104 – 110</td><td class="px-4 py-2.5 text-neutral-600">54 – 56</td><td class="px-4 py-2.5 text-neutral-600">52 – 53</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">38</td><td class="px-4 py-2.5 text-neutral-600">6 – 7 years</td><td class="px-4 py-2.5 text-neutral-600">110 – 116</td><td class="px-4 py-2.5 text-neutral-600">56 – 58</td><td class="px-4 py-2.5 text-neutral-600">53 – 55</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">40</td><td class="px-4 py-2.5 text-neutral-600">7 – 8 years</td><td class="px-4 py-2.5 text-neutral-600">116 – 122</td><td class="px-4 py-2.5 text-neutral-600">58 – 61</td><td class="px-4 py-2.5 text-neutral-600">55 – 57</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">42</td><td class="px-4 py-2.5 text-neutral-600">8 – 9 years</td><td class="px-4 py-2.5 text-neutral-600">122 – 128</td><td class="px-4 py-2.5 text-neutral-600">61 – 64</td><td class="px-4 py-2.5 text-neutral-600">57 – 59</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">44</td><td class="px-4 py-2.5 text-neutral-600">9 – 10 years</td><td class="px-4 py-2.5 text-neutral-600">128 – 134</td><td class="px-4 py-2.5 text-neutral-600">64 – 67</td><td class="px-4 py-2.5 text-neutral-600">59 – 61</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">46</td><td class="px-4 py-2.5 text-neutral-600">10 – 12 years</td><td class="px-4 py-2.5 text-neutral-600">134 – 146</td><td class="px-4 py-2.5 text-neutral-600">67 – 72</td><td class="px-4 py-2.5 text-neutral-600">61 – 64</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">48</td><td class="px-4 py-2.5 text-neutral-600">12 – 13 years</td><td class="px-4 py-2.5 text-neutral-600">146 – 152</td><td class="px-4 py-2.5 text-neutral-600">72 – 76</td><td class="px-4 py-2.5 text-neutral-600">64 – 66</td></tr>
                                <tr class="hover:bg-neutral-50/50"><td class="px-4 py-2.5 font-medium text-neutral-900">50</td><td class="px-4 py-2.5 text-neutral-600">13 – 14 years</td><td class="px-4 py-2.5 text-neutral-600">152 – 158</td><td class="px-4 py-2.5 text-neutral-600">76 – 80</td><td class="px-4 py-2.5 text-neutral-600">66 – 68</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- How to Measure -->
                <div class="bg-white rounded-xl border border-neutral-100 p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-neutral-900 mb-5">How to Measure</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#6F9CA2]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Height</h3>
                            <p class="text-sm text-neutral-600 leading-relaxed">Stand your child against a wall without shoes. Measure from the top of the head to the floor.</p>
                        </div>
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#F8931D]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#F8931D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Chest</h3>
                            <p class="text-sm text-neutral-600 leading-relaxed">Wrap a measuring tape around the fullest part of the chest, keeping it level under the arms.</p>
                        </div>
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#6F9CA2]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#6F9CA2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Waist</h3>
                            <p class="text-sm text-neutral-600 leading-relaxed">Measure around the natural waistline (the narrowest point), keeping the tape snug but not tight.</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <p class="text-sm text-amber-800"><strong>Tip:</strong> If your child's measurements fall between two sizes, we recommend choosing the larger size for a more comfortable fit and room to grow.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>
</x-layouts.app>
