{{-- Direction Modal — must be inside x-data="sApp()" scope --}}
<div x-show="dirModal" x-cloak @keydown.escape.window="dirModal=false"
     class="fixed inset-0 z-[400] backdrop-blur-sm" style="background:rgba(11,28,48,.5)">
    <div class="flex items-center justify-center w-full h-full p-5" @click.self="dirModal=false">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col"
             style="box-shadow:0 24px 60px rgba(11,28,48,.2)">

            {{-- Header --}}
            <div class="px-6 py-4 flex items-start justify-between gap-3 shrink-0" style="border-bottom:1px solid #f3f4f6">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0" style="background:#eff4ff">
                            <span class="material-symbols-outlined" style="font-size:14px;color:#004ac6">account_tree</span>
                        </div>
                        <h3 class="text-[15px] font-bold m-0" style="color:#0b1c30" x-text="dirModalName"></h3>
                    </div>
                    <p class="text-[11px] m-0 ml-8" style="color:#434655">
                        <span x-text="dirModalTeachers.length"></span> ta o'qituvchi baholangan
                    </p>
                </div>
                <button @click="dirModal=false"
                        class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer text-[15px] flex items-center justify-center leading-none"
                        style="background:#f3f4f6;color:#434655"
                        onmouseover="this.style.background='#e5e7eb'"
                        onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            {{-- Summary bar --}}
            <div class="px-6 py-3 shrink-0 border-b" style="background:#f9fafb;border-color:#f3f4f6">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px]" style="color:#434655">Yo'nalish o'rtacha balli</span>
                    <span class="text-[13px] font-extrabold tabular-nums"
                          :style="`color:${dirModalScore>=4.5?'#006c49':dirModalScore>=3.5?'#784b00':'#ba1a1a'}`"
                          x-text="dirModalScore.toFixed(2)+' / 5.00'"></span>
                </div>
                <div class="h-1.5 rounded-full overflow-hidden" style="background:#e5e7eb">
                    <div class="h-full rounded-full transition-[width] duration-700"
                         :style="`width:${dirModalScore/5*100}%;background:${dirModalScore>=4.5?'#006c49':dirModalScore>=3.5?'#784b00':'#ba1a1a'}`"></div>
                </div>
            </div>

            {{-- Teachers list --}}
            <div class="overflow-y-auto flex-1">
                <template x-for="(t, i) in dirModalTeachers" :key="i">
                    <div :style="`display:flex;align-items:center;gap:14px;padding:14px 22px;${i<dirModalTeachers.length-1?'border-bottom:1px solid #f9fafb':''}`">
                        <div class="w-9 h-9 rounded-lg shrink-0 flex items-center justify-center text-white text-[12px] font-bold"
                             style="background:linear-gradient(135deg,#004ac6,#2563eb)"
                             x-text="t.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold m-0 truncate" style="color:#0b1c30" x-text="t.name"></p>
                            <p class="text-[11px] m-0" style="color:#434655" x-text="t.cnt+' ta baholash'"></p>
                        </div>
                        <div class="shrink-0 text-right min-w-28">
                            <div class="flex items-center gap-2 justify-end mb-1">
                                <div class="w-16 h-1.5 rounded-full overflow-hidden" style="background:#e5e7eb">
                                    <div :style="`height:100%;border-radius:99px;width:${t.score/5*100}%;background:${t.score>=4.5?'#006c49':t.score>=3.5?'#784b00':'#ba1a1a'};transition:width .6s ease`"></div>
                                </div>
                                <span class="text-[14px] font-extrabold tabular-nums"
                                      :style="`color:${t.score>=4.5?'#006c49':t.score>=3.5?'#784b00':'#ba1a1a'}`"
                                      x-text="t.score.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-end gap-px">
                                <template x-for="s in 5" :key="s">
                                    <span :style="`font-size:9px;color:${s<=Math.round(t.score)?'#784b00':'#E5E7EB'}`">★</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="dirModalTeachers.length===0" class="p-12 text-center" style="color:#9ca3af">
                    <span class="material-symbols-outlined block mb-2" style="font-size:28px;opacity:.2">groups</span>
                    <p class="text-[13px] m-0">Bu yo'nalishda hali baholash yo'q</p>
                </div>
            </div>

        </div>
    </div>
</div>
