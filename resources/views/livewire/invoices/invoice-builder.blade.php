<div class="space-y-6">
    <x-slot name="header">Invoice Builder</x-slot>

    <!-- Page Header & Download CTA -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-file-invoice-dollar text-sky-500"></i> Multi-Brand Invoice Builder
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Customize, live-preview, and export branded PDF invoices for any company or website.</p>
        </div>

        <form action="{{ route('invoices.download-pdf') }}" method="POST" target="_blank">
            @csrf
            <!-- Hidden inputs to pass full state to PDF downloader -->
            <input type="hidden" name="sellerName" value="{{ $sellerName }}">
            <input type="hidden" name="sellerRegNo" value="{{ $sellerRegNo }}">
            <input type="hidden" name="sellerAddress" value="{{ $sellerAddress }}">
            <input type="hidden" name="sellerEmail" value="{{ $sellerEmail }}">
            <input type="hidden" name="sellerPhone" value="{{ $sellerPhone }}">
            <input type="hidden" name="sellerWebsite" value="{{ $sellerWebsite }}">
            <input type="hidden" name="brandColor" value="{{ $brandColor }}">
            <input type="hidden" name="templateLayout" value="{{ $templateLayout }}">
            <input type="hidden" name="paymentMethod" value="{{ $paymentMethod }}">

            <input type="hidden" name="invoiceNumber" value="{{ $invoiceNumber }}">
            <input type="hidden" name="issueDate" value="{{ $issueDate }}">
            <input type="hidden" name="dueDate" value="{{ $dueDate }}">
            <input type="hidden" name="currency" value="{{ $currency }}">
            <input type="hidden" name="currencySymbol" value="{{ $currencySymbol }}">

            <input type="hidden" name="clientName" value="{{ $clientName }}">
            <input type="hidden" name="clientEmail" value="{{ $clientEmail }}">
            <input type="hidden" name="clientAddress" value="{{ $clientAddress }}">
            <input type="hidden" name="clientVatNo" value="{{ $clientVatNo }}">

            @foreach($items as $idx => $item)
                <input type="hidden" name="items[{{ $idx }}][description]" value="{{ $item['description'] }}">
                <input type="hidden" name="items[{{ $idx }}][quantity]" value="{{ $item['quantity'] }}">
                <input type="hidden" name="items[{{ $idx }}][unit_price]" value="{{ $item['unit_price'] }}">
            @endforeach

            <input type="hidden" name="taxPercent" value="{{ $taxPercent }}">
            <input type="hidden" name="discountAmount" value="{{ $discountAmount }}">

            <input type="hidden" name="bankName" value="{{ $bankName }}">
            <input type="hidden" name="accountName" value="{{ $accountName }}">
            <input type="hidden" name="accountNumber" value="{{ $accountNumber }}">
            <input type="hidden" name="sortCode" value="{{ $sortCode }}">
            <input type="hidden" name="iban" value="{{ $iban }}">
            <input type="hidden" name="swift" value="{{ $swift }}">
            <input type="hidden" name="notes" value="{{ $notes }}">

            <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer">
                <i class="fa-solid fa-file-pdf text-base"></i> Download PDF Invoice
            </button>
        </form>
    </div>

    <!-- Main Grid: Split Layout (Editor Left, Live Preview Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT PANEL: Editor & Brand Selector (5 Columns) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- 1. Brand Selection Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <i class="fa-solid fa-building text-sky-500"></i> Company & Brand Style
                </h3>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Invoice Design Layout Template</label>
                        <select wire:model.live="templateLayout" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-sky-700 dark:text-sky-400 focus:ring-2 focus:ring-sky-500/20">
                            <option value="jumlee">🟣 Jumlee Style (Vivid Purple Block & Centered Serif)</option>
                            <option value="bordeux">🔵 Bordeux Style (Navy Minimalist Clean Lines)</option>
                            <option value="electro">🌊 Electro-Base Style (Organic Wave Art Accent)</option>
                            <option value="standard">💼 Standard Corporate (Modern Slate Blue)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Company / Brand</label>
                        <select wire:model.live="selectedProjectId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-sky-500/20">
                            <option value="">-- Manual Company Details --</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($websites->count() > 0)
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Website Domain</label>
                            <select wire:model.live="selectedWebsiteId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-sky-500/20">
                                @foreach($websites as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->url }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Seller Name</label>
                            <input type="text" wire:model.live="sellerName" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Brand Accent Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live="brandColor" class="h-8 w-12 rounded border border-slate-200 cursor-pointer">
                                <span class="text-xs font-mono text-slate-600 dark:text-slate-300">{{ $brandColor }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Support Email</label>
                            <input type="email" wire:model.live="sellerEmail" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Company Reg No</label>
                            <input type="text" wire:model.live="sellerRegNo" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Invoice Meta & Client Details -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <i class="fa-solid fa-user-tag text-sky-500"></i> Invoice & Client Info
                </h3>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Invoice #</label>
                        <input type="text" wire:model.live="invoiceNumber" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Issue Date</label>
                        <input type="date" wire:model.live="issueDate" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Currency</label>
                        <select wire:model.live="currency" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                            <option value="GBP">GBP (£)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="USD">USD ($)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">Client (Bill To)</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <input type="text" wire:model.live="clientName" placeholder="Client / Business Name" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                        <div>
                            <input type="email" wire:model.live="clientEmail" placeholder="Client Email" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                        </div>
                    </div>
                    <div>
                        <textarea wire:model.live="clientAddress" rows="2" placeholder="Client Address (optional)" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- 3. Line Items Editor -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-sky-500"></i> Line Items
                    </h3>
                    <button type="button" wire:click="addItem" class="text-xs font-bold text-sky-600 hover:text-sky-500 cursor-pointer flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Add Item
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($items as $idx => $item)
                        <div class="p-3 bg-slate-50 dark:bg-slate-950/60 rounded-xl border border-slate-200/50 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase text-slate-400">Item #{{ $idx + 1 }}</span>
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $idx }})" class="text-rose-500 hover:text-rose-700 text-xs" title="Remove Item">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                @endif
                            </div>
                            <input type="text" wire:model.live="items.{{ $idx }}.description" placeholder="Description / Service details..." class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-medium">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Qty</label>
                                    <input type="number" step="0.1" wire:model.live="items.{{ $idx }}.quantity" class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Unit Price ({{ $currencySymbol }})</label>
                                    <input type="number" step="0.01" wire:model.live="items.{{ $idx }}.unit_price" class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Tax & Discount Adjustments -->
                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tax Rate (%)</label>
                        <input type="number" step="0.5" wire:model.live="taxPercent" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Discount ({{ $currencySymbol }})</label>
                        <input type="number" step="0.01" wire:model.live="discountAmount" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                    </div>
                </div>
            </div>

            <!-- 4. Payment Details Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-3">
                <h3 class="font-outfit font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <i class="fa-solid fa-piggy-bank text-sky-500"></i> Payment & Bank Details
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Bank Name</label>
                        <input type="text" wire:model.live="bankName" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Account / Sort Code</label>
                        <input type="text" wire:model.live="accountNumber" placeholder="e.g. 12345678 (20-00-00)" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">IBAN / SWIFT</label>
                    <input type="text" wire:model.live="iban" placeholder="GB00 BARC 2000 0012 3456 78" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-mono">
                </div>
            </div>

        </div>

        <!-- RIGHT PANEL: Live Invoice Preview (7 Columns) -->
        <div class="lg:col-span-7">
            <div class="sticky top-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Preview Header -->
                <div class="bg-slate-100 dark:bg-slate-950 px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-2">
                        <i class="fa-solid fa-eye text-sky-500"></i> Real-Time Live Invoice Preview
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold text-white uppercase tracking-wider" style="background-color: {{ $brandColor }}">
                        {{ strtoupper($templateLayout) }} Layout
                    </span>
                </div>

                <!-- Invoice Content Sheet -->
                <div class="p-8 space-y-6 text-slate-800 dark:text-slate-100 bg-white dark:bg-slate-900 min-h-[600px] relative overflow-hidden">

                    @if($templateLayout === 'jumlee')
                        <!-- JUMLEE STYLE PREVIEW -->
                        <div class="space-y-6">
                            <h2 class="text-4xl font-serif font-black tracking-widest text-center uppercase text-slate-900 dark:text-white">INVOICE</h2>

                            <div class="grid grid-cols-2 gap-6 text-xs">
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white uppercase block mb-1 text-[11px]">INVOICE TO:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $clientName ?: 'Client Name' }}</div>
                                    @if($clientEmail)<div class="text-slate-500">{{ $clientEmail }}</div>@endif
                                    @if($clientAddress)<div class="text-slate-500">{{ $clientAddress }}</div>@endif
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 dark:text-white uppercase block mb-1 text-[11px]">INVOICE FROM:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $sellerName }}</div>
                                    @if($sellerAddress)<div class="text-slate-500">{{ $sellerAddress }}</div>@endif
                                    @if($sellerEmail)<div class="text-slate-500">{{ $sellerEmail }}</div>@endif
                                    @if($sellerPhone)<div class="text-slate-500">{{ $sellerPhone }}</div>@endif
                                </div>
                            </div>

                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="text-white text-xs font-bold uppercase" style="background-color: {{ $brandColor }}">
                                        <th class="py-3 px-4">PRODUCT</th>
                                        <th class="py-3 px-4 text-right">PRICE</th>
                                        <th class="py-3 px-4 text-center">QTY</th>
                                        <th class="py-3 px-4 text-right">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-white dark:border-slate-900">
                                            <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">{{ $item['description'] }}</td>
                                            <td class="py-3.5 px-4 text-right">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                                            <td class="py-3.5 px-4 text-center font-bold">{{ $item['quantity'] }}</td>
                                            <td class="py-3.5 px-4 text-right font-bold">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-end">
                                <div class="w-72 text-xs space-y-2">
                                    <div class="flex justify-between font-bold text-slate-600 dark:text-slate-300">
                                        <span>SUB-TOTAL</span>
                                        <span>{{ $currencySymbol }}{{ number_format($this->subtotal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between font-bold text-white px-4 py-2.5 rounded-md" style="background-color: {{ $brandColor }}">
                                        <span>TOTAL</span>
                                        <span>{{ $currencySymbol }}{{ number_format($this->totalDue, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 font-bold text-xs space-y-1 text-slate-900 dark:text-slate-100 uppercase">
                                <div>PAYMENT METHOD: {{ $paymentMethod }}</div>
                                <div>DATE: {{ date('d.m.Y', strtotime($issueDate)) }}</div>
                                <div>INVOICE ID: #{{ $invoiceNumber }}</div>
                            </div>

                            <div class="h-2 rounded-full w-full" style="background-color: {{ $brandColor }}"></div>
                        </div>

                    @elseif($templateLayout === 'bordeux')
                        <!-- BORDEUX NAVY MINIMALIST PREVIEW -->
                        <div class="space-y-6">
                            <div class="flex items-start justify-between">
                                <div class="text-xs space-y-0.5 text-slate-600 dark:text-slate-400">
                                    <div>Date : <strong>{{ date('d M, Y', strtotime($issueDate)) }}</strong></div>
                                    <div>Invoice No. <strong>{{ $invoiceNumber }}</strong></div>
                                </div>
                                <div class="text-4xl font-black tracking-wider uppercase" style="color: {{ $brandColor }}">INVOICE</div>
                            </div>

                            <div class="h-0.5 w-full" style="background-color: {{ $brandColor }}"></div>

                            <div class="grid grid-cols-2 gap-6 text-xs">
                                <div>
                                    <span class="font-bold uppercase text-slate-900 dark:text-white block mb-1">BILLED TO:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $clientName ?: 'Client Name' }}</div>
                                    @if($clientAddress)<div class="text-slate-500">{{ $clientAddress }}</div>@endif
                                    @if($clientEmail)<div class="text-slate-500">{{ $clientEmail }}</div>@endif
                                </div>
                                <div>
                                    <span class="font-bold uppercase text-slate-900 dark:text-white block mb-1">FROM:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $sellerName }}</div>
                                    @if($sellerAddress)<div class="text-slate-500">{{ $sellerAddress }}</div>@endif
                                    @if($sellerPhone)<div class="text-slate-500">{{ $sellerPhone }}</div>@endif
                                    @if($sellerEmail)<div class="text-slate-500">{{ $sellerEmail }}</div>@endif
                                </div>
                            </div>

                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-y-2 border-slate-900 dark:border-white text-slate-900 dark:text-white font-bold uppercase">
                                        <th class="py-2.5 px-3">DESCRIPTION</th>
                                        <th class="py-2.5 px-3 text-center">QUANTITY</th>
                                        <th class="py-2.5 px-3 text-right">PRICE</th>
                                        <th class="py-2.5 px-3 text-right">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="py-3 px-3 font-medium border-r border-slate-200 dark:border-slate-800">{{ $item['description'] }}</td>
                                            <td class="py-3 px-3 text-center font-semibold border-r border-slate-200 dark:border-slate-800">{{ $item['quantity'] }}</td>
                                            <td class="py-3 px-3 text-right border-r border-slate-200 dark:border-slate-800">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                                            <td class="py-3 px-3 text-right font-bold">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-end">
                                <div class="w-64 space-y-1.5 text-xs text-right">
                                    <div class="text-slate-500">Subtotal: <strong class="text-slate-800 dark:text-slate-200">{{ $currencySymbol }}{{ number_format($this->subtotal, 2) }}</strong></div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white pt-1">Grand Total: <strong style="color: {{ $brandColor }}">{{ $currencySymbol }}{{ number_format($this->totalDue, 2) }}</strong></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-slate-800 text-xs">
                                <div>
                                    <div class="font-bold uppercase text-[11px] text-slate-900 dark:text-white">PAYMENT INFORMATION</div>
                                    <div class="text-slate-500 mt-0.5">{{ $paymentMethod }}</div>
                                </div>
                                <div class="font-bold text-sm uppercase text-slate-900 dark:text-white">
                                    THANK YOU FOR YOUR PURCHASE!
                                </div>
                            </div>
                        </div>

                    @elseif($templateLayout === 'electro')
                        <!-- ELECTRO BASE ORGANIC WAVE ART PREVIEW -->
                        <div class="space-y-6 relative z-10">
                            <!-- Background wave accent effect -->
                            <div class="absolute -top-8 -right-8 w-44 h-44 rounded-full bg-sky-200/50 dark:bg-sky-900/20 blur-2xl pointer-events-none"></div>

                            <div class="flex items-start justify-between">
                                <div class="text-4xl font-serif font-bold text-slate-900 dark:text-white">Invoice</div>
                                <div class="text-right text-xs">
                                    <div class="font-bold text-sm text-slate-900 dark:text-white">Invoice No. {{ $invoiceNumber }}</div>
                                    <div class="text-slate-500 mt-1">{{ date('d.m.Y', strtotime($issueDate)) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 text-xs">
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block mb-1">From:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $sellerName }}</div>
                                    @if($sellerAddress)<div class="text-slate-500">{{ $sellerAddress }}</div>@endif
                                    @if($sellerEmail)<div class="text-slate-500">{{ $sellerEmail }}</div>@endif
                                    @if($sellerPhone)<div class="text-slate-500">{{ $sellerPhone }}</div>@endif
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 dark:text-white block mb-1">To:</span>
                                    <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $clientName ?: 'Client Name' }}</div>
                                    @if($clientEmail)<div class="text-slate-500">{{ $clientEmail }}</div>@endif
                                    @if($clientAddress)<div class="text-slate-500">{{ $clientAddress }}</div>@endif
                                </div>
                            </div>

                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-y-2 border-slate-900 dark:border-white font-bold text-slate-900 dark:text-white">
                                        <th class="py-2.5 px-2">Description</th>
                                        <th class="py-2.5 px-2 text-center">Quantity</th>
                                        <th class="py-2.5 px-2 text-right">Rate</th>
                                        <th class="py-2.5 px-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-900 dark:divide-slate-100">
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="py-3 px-2 font-medium">{{ $item['description'] }}</td>
                                            <td class="py-3 px-2 text-center font-semibold">{{ $item['quantity'] }}</td>
                                            <td class="py-3 px-2 text-right">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                                            <td class="py-3 px-2 text-right font-bold">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-end">
                                <div class="w-60 flex justify-between font-bold text-sm text-slate-900 dark:text-white">
                                    <span class="uppercase">TOTAL</span>
                                    <span>{{ $currencySymbol }}{{ number_format($this->totalDue, 2) }}</span>
                                </div>
                            </div>

                            <div class="text-right text-xs pt-4">
                                <div class="font-bold font-serif text-sm text-slate-900 dark:text-white">Payment details</div>
                                <div class="text-slate-500 mt-0.5">Payment was successfully completed using VISA ending in 6714</div>
                            </div>
                        </div>

                    @else
                        <!-- STANDARD CORPORATE PREVIEW -->
                        <div class="space-y-6">
                            <div class="flex items-start justify-between border-b-2 pb-5" style="border-color: {{ $brandColor }}">
                                <div>
                                    <h2 class="text-2xl font-extrabold tracking-tight" style="color: {{ $brandColor }}">{{ $sellerName ?: 'Company Name' }}</h2>
                                    @if($sellerWebsite)<div class="text-xs text-slate-500 mt-0.5">{{ $sellerWebsite }}</div>@endif
                                    @if($sellerEmail)<div class="text-xs text-slate-500">{{ $sellerEmail }}</div>@endif
                                    @if($sellerRegNo)<div class="text-[11px] text-slate-400 mt-1">Reg No: {{ $sellerRegNo }}</div>@endif
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-black tracking-widest text-slate-800 dark:text-slate-100 uppercase">INVOICE</div>
                                    <div class="text-sm font-bold text-slate-500 mt-1">#{{ $invoiceNumber }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Date: <strong>{{ $issueDate }}</strong></div>
                                </div>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-950/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Billed To</span>
                                <div class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $clientName ?: 'Client / Business Name' }}</div>
                                @if($clientEmail)<div class="text-xs text-slate-500">{{ $clientEmail }}</div>@endif
                                @if($clientAddress)<div class="text-xs text-slate-500 mt-1 whitespace-pre-line">{{ $clientAddress }}</div>@endif
                            </div>

                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="text-white text-xs font-bold uppercase" style="background-color: {{ $brandColor }}">
                                        <th class="py-2.5 px-3 rounded-l-lg">Description</th>
                                        <th class="py-2.5 px-3 text-center">Qty</th>
                                        <th class="py-2.5 px-3 text-right">Price</th>
                                        <th class="py-2.5 px-3 text-right rounded-r-lg">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="py-3 px-3 font-medium">{{ $item['description'] ?: 'Service description' }}</td>
                                            <td class="py-3 px-3 text-center font-bold text-slate-600 dark:text-slate-400">{{ $item['quantity'] }}</td>
                                            <td class="py-3 px-3 text-right font-medium">{{ $currencySymbol }}{{ number_format((float)$item['unit_price'], 2) }}</td>
                                            <td class="py-3 px-3 text-right font-bold text-slate-800 dark:text-slate-100">{{ $currencySymbol }}{{ number_format((float)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="flex justify-end">
                                <div class="w-64 space-y-1.5 text-xs bg-slate-50 dark:bg-slate-950/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                    <div class="flex justify-between text-slate-500">
                                        <span>Subtotal:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $currencySymbol }}{{ number_format($this->subtotal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm font-extrabold pt-2 border-t border-slate-200 dark:border-slate-800" style="color: {{ $brandColor }}">
                                        <span>Total Due:</span>
                                        <span>{{ $currencySymbol }}{{ number_format($this->totalDue, 2) }} {{ $currency }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>
