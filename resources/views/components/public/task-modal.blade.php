{{-- Task Modal — must be inside x-data="tApp()" scope --}}
<div x-show="modal" x-cloak @keydown.escape.window="modal=false"
     class="fixed inset-0 z-[300] backdrop-blur-sm" style="background:rgba(11,28,48,.5)">
    <div class="flex items-center justify-center w-full h-full p-5" @click.self="modal=false">
        <div class="bg-white rounded-2xl w-full max-w-xl max-h-[88vh] overflow-hidden flex flex-col"
             style="box-shadow:0 24px 60px rgba(11,28,48,.2)">

            {{-- Header --}}
            <div class="px-6 py-4 flex items-start justify-between gap-3 shrink-0" style="border-bottom:1px solid #f3f4f6">
                <div>
                    <h3 class="text-[15px] font-bold m-0" style="color:#0b1c30" x-text="modalTeacher"></h3>
                    <p class="text-xs mt-0.5 m-0" style="color:#434655">Topshiriqlar ro'yxati</p>
                </div>
                <button @click="modal=false"
                        class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer text-[15px] flex items-center justify-center leading-none"
                        style="background:#f3f4f6;color:#434655"
                        onmouseover="this.style.background='#e5e7eb'"
                        onmouseout="this.style.background='#f3f4f6'">✕</button>
            </div>

            {{-- Progress bar --}}
            <div class="px-6 py-3 shrink-0 border-b" style="background:#f9fafb;border-color:#f3f4f6">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px]" style="color:#434655">
                        Bajarilgan: <strong class="text-emerald-500" x-text="modalTasks.filter(t=>t.status==='completed').length"></strong>
                        / <span x-text="modalTasks.length"></span> ta
                    </span>
                    <span class="text-[11px] font-bold" style="color:#0b1c30"
                          x-text="modalTasks.length?Math.round(modalTasks.filter(t=>t.status==='completed').length/modalTasks.length*100)+'%':'0%'"></span>
                </div>
                <div class="h-1.5 rounded-full overflow-hidden" style="background:#fee2e2">
                    <div class="h-full rounded-full transition-[width] duration-700"
                         style="background:linear-gradient(90deg,#006c49,#2d9e6b)"
                         :style="`width:${modalTasks.length?Math.round(modalTasks.filter(t=>t.status==='completed').length/modalTasks.length*100):0}%`"></div>
                </div>
            </div>

            {{-- Tasks list --}}
            <div class="overflow-y-auto flex-1">
                <template x-for="(task,i) in modalTasks" :key="i">
                    <div :style="`display:flex;align-items:flex-start;gap:12px;padding:13px 22px;${i<modalTasks.length-1?'border-bottom:1px solid #f9fafb':''}`">
                        <div :style="`width:32px;height:32px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;background:${task.status==='completed'?'#dcfce7':'#fee2e2'}`">
                            <span x-text="task.status==='completed'?'✅':'⏳'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <p class="text-[13px] font-semibold m-0 flex-1" style="color:#0b1c30" x-text="task.title"></p>
                                <span :style="`font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;flex-shrink:0;${task.status==='completed'?'background:#dcfce7;color:#006c49':'background:#fee2e2;color:#ba1a1a'}`"
                                      x-text="task.status==='completed'?'Bajarilgan':'Kutilmoqda'"></span>
                            </div>
                            <div class="flex items-center gap-2.5 mt-1 flex-wrap">
                                <span x-show="task.done_at" class="text-[11px] flex items-center gap-0.5" style="color:#006c49">
                                    <span>✓</span><span x-text="task.done_at"></span>
                                </span>
                                <span x-show="task.due && task.status!=='completed'" class="text-[11px] flex items-center gap-0.5" style="color:#784b00">
                                    <span>📅</span><span x-text="task.due"></span>
                                </span>
                                <span x-show="task.priority && task.priority!=='medium'"
                                      :style="`font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;${task.priority==='high'?'background:#fee2e2;color:#ba1a1a':task.priority==='urgent'?'background:#fef3c7;color:#784b00':'background:#f3f4f6;color:#6b7280'}`"
                                      x-text="task.priority==='high'?'🔴 Yuqori':task.priority==='urgent'?'🔥 Shoshilinch':'Oddiy'"></span>
                            </div>
                            <p x-show="task.note" class="text-[11px] mt-1 m-0 italic leading-snug" style="color:#9ca3af"
                               x-text="'💬 '+task.note"></p>
                        </div>
                    </div>
                </template>
                <div x-show="modalTasks.length===0" class="p-12 text-center" style="color:#9ca3af">
                    <div class="text-[28px] mb-2 opacity-30">📋</div>
                    <p class="text-[13px] m-0">Topshiriqlar tayinlanmagan</p>
                </div>
            </div>

        </div>
    </div>
</div>
