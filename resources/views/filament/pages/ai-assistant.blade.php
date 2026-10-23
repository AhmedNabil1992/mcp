<x-filament-panels::page>
    <div x-data="mcpVoiceAssistant($wire)" x-on:beforeunload.window="stopVoice(false)" class="mcp-assistant mx-auto flex h-[calc(100vh-14rem)] min-h-[560px] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-gray-900 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm shadow-primary-600/20"><x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" /></div>
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-gray-950 dark:text-white">{{ __('mcp::filament/pages/ai-assistant.heading') }}</h2>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ __('mcp::filament/pages/ai-assistant.connected') }}</p>
                </div>
            </div>

            <div class="order-3 flex w-full items-center justify-center sm:order-none sm:w-auto">
                <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-white/5" role="tablist" aria-label="{{ __('mcp::filament/pages/ai-assistant.mode.label') }}">
                    <button type="button" x-on:click="selectTextMode" :class="mode === 'text' ? 'bg-white text-primary-600 shadow-sm dark:bg-white/10 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'" class="flex min-w-24 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition" role="tab" :aria-selected="mode === 'text'">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-4 w-4" /> {{ __('mcp::filament/pages/ai-assistant.mode.text') }}
                    </button>
                    <button type="button" x-on:click="selectVoiceMode" :class="mode === 'voice' ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/25' : 'text-gray-500 dark:text-gray-400'" class="flex min-w-24 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition" role="tab" :aria-selected="mode === 'voice'">
                        <x-filament::icon icon="heroicon-o-speaker-wave" class="h-4 w-4" /> {{ __('mcp::filament/pages/ai-assistant.mode.voice') }}
                    </button>
                </div>
            </div>

            <x-filament::button color="gray" size="sm" icon="heroicon-o-arrow-path" wire:click="clearChat">{{ __('mcp::filament/pages/ai-assistant.clear') }}</x-filament::button>
        </header>

        <section x-show="mode === 'text'" class="flex min-h-0 flex-1 flex-col">
            <div class="flex gap-2 overflow-x-auto border-b border-gray-100 px-6 py-3 text-xs dark:border-white/10">
                @foreach(__('mcp::filament/pages/ai-assistant.prompts') as $prompt)
                    <button type="button" wire:click="askQuestion({{ Illuminate\Support\Js::from($prompt) }})" class="whitespace-nowrap rounded-full border border-gray-200 px-3 py-1.5 text-gray-700 transition hover:border-primary-500 hover:text-primary-600 dark:border-white/10 dark:text-gray-300">{{ $prompt }}</button>
                @endforeach
            </div>
            <div id="mcp-ai-messages" class="flex-1 space-y-5 overflow-y-auto bg-gray-50 p-6 dark:bg-gray-950">
                @foreach($messages as $index => $item)
                    <div wire:key="mcp-ai-message-{{ $item['id'] ?? $index }}" class="flex {{ $item['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-7 {{ $item['role'] === 'user' ? 'bg-primary-600 text-white' : 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-white dark:ring-white/10' }}">
                            @if($item['role'] === 'assistant')<div class="prose prose-sm max-w-none dark:prose-invert">{!! Str::markdown($item['content']) !!}</div>@else<div class="whitespace-pre-wrap">{{ $item['content'] }}</div>@endif
                            <div class="mt-1 text-[10px] opacity-70">{{ $item['time'] }}</div>
                        </div>
                    </div>
                @endforeach
                <div wire:loading wire:target="sendMessage,askQuestion" class="text-sm text-gray-500">{{ __('mcp::filament/pages/ai-assistant.thinking') }}</div>
            </div>
            <form wire:submit="sendMessage" class="border-t border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-end gap-3">
                    <textarea wire:model="message" rows="2" maxlength="4000" required class="block w-full resize-none rounded-xl border-0 bg-gray-50 px-4 py-3 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10" placeholder="{{ __('mcp::filament/pages/ai-assistant.placeholder') }}"></textarea>
                    <x-filament::button type="submit" icon="heroicon-o-paper-airplane" wire:loading.attr="disabled" wire:target="sendMessage">{{ __('mcp::filament/pages/ai-assistant.send') }}</x-filament::button>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ __('mcp::filament/pages/ai-assistant.read-only') }}</p>
            </form>
        </section>

        <section x-show="mode === 'voice'" x-cloak wire:ignore class="relative flex min-h-0 flex-1 flex-col overflow-hidden bg-white dark:bg-gray-950">
            <div class="pointer-events-none absolute inset-0 voice-backdrop"></div>
            <div class="relative flex min-h-0 flex-1 flex-col items-center justify-center px-5 py-6 text-center sm:px-10">
                <div class="voice-stage" :class="{ 'is-live': isActive && !isMuted, 'is-speaking': isSpeaking, 'is-muted': isMuted }" :style="`--voice-level:${level}`">
                    <span class="voice-ring voice-ring-one"></span><span class="voice-ring voice-ring-two"></span><span class="voice-ring voice-ring-three"></span>
                    <div class="voice-orb" aria-hidden="true"><span class="voice-orb-layer voice-orb-layer-one"></span><span class="voice-orb-layer voice-orb-layer-two"></span><span class="voice-orb-highlight"></span></div>
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-950 dark:text-white" x-text="statusLabel"></h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="modelLabel"></p>
                <div class="mt-5 min-h-16 w-full max-w-2xl rounded-2xl bg-gray-50 px-5 py-3 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10" aria-live="polite">
                    <p class="line-clamp-2 text-sm leading-7 text-gray-700 dark:text-gray-200" x-text="displayTranscript || '{{ __('mcp::filament/pages/ai-assistant.voice.prompt') }}'"></p>
                </div>
                <template x-if="errorMessage"><p class="mt-3 max-w-xl text-sm text-danger-600 dark:text-danger-400" x-text="errorMessage"></p></template>
                <div class="mt-6 flex items-center justify-center gap-5">
                    <button type="button" x-on:click="toggleMute" :disabled="!isActive" class="voice-control border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300" :aria-label="isMuted ? '{{ __('mcp::filament/pages/ai-assistant.voice.unmute') }}' : '{{ __('mcp::filament/pages/ai-assistant.voice.mute') }}'">
                        <x-filament::icon x-show="!isMuted" icon="heroicon-o-microphone" class="h-6 w-6" /><x-filament::icon x-show="isMuted" x-cloak icon="heroicon-o-speaker-x-mark" class="h-6 w-6" />
                    </button>
                    <button type="button" x-on:click="isActive ? stopVoice() : startVoice()" class="voice-main-control" :class="isActive ? 'is-active' : ''" :aria-label="isActive ? '{{ __('mcp::filament/pages/ai-assistant.voice.stop') }}' : '{{ __('mcp::filament/pages/ai-assistant.voice.start') }}'">
                        <x-filament::icon x-show="!isActive" icon="heroicon-o-microphone" class="h-8 w-8" /><x-filament::icon x-show="isActive" x-cloak icon="heroicon-o-stop" class="h-7 w-7" />
                    </button>
                    <button type="button" x-on:click="stopVoice(); mode = 'text'" class="voice-control border border-danger-200 bg-danger-50 text-danger-600 hover:bg-danger-100 dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-400" aria-label="{{ __('mcp::filament/pages/ai-assistant.voice.end') }}"><x-filament::icon icon="heroicon-o-phone-x-mark" class="h-6 w-6" /></button>
                </div>
                <div class="mt-4 flex h-5 items-center justify-center gap-1" aria-hidden="true"><template x-for="bar in 9" :key="bar"><span class="voice-meter-bar" :style="`height:${4 + (bar % 5) * 2 + level * 14}px`"></span></template></div>
            </div>
            <p class="relative border-t border-gray-100 px-5 py-3 text-center text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">{{ __('mcp::filament/pages/ai-assistant.voice.privacy') }}</p>
        </section>
    </div>

    <style>
        [x-cloak] { display:none!important } .voice-backdrop { background:radial-gradient(circle at 50% 44%,rgb(59 130 246/.09),transparent 43%) }
        .voice-stage{--voice-level:0;position:relative;width:220px;height:220px;display:grid;place-items:center}.voice-ring{position:absolute;inset:18px;border:1px solid rgb(59 130 246/.14);border-radius:999px;opacity:0;transform:scale(.72)}.voice-ring-two{inset:4px;animation-delay:.7s!important}.voice-ring-three{inset:-12px;animation-delay:1.4s!important}.voice-stage.is-live .voice-ring{animation:mcp-voice-ring 2.2s ease-out infinite}
        .voice-orb{position:relative;width:148px;height:148px;overflow:hidden;border-radius:999px;background:linear-gradient(145deg,#2563eb 5%,#4f46e5 42%,#22d3ee 100%);box-shadow:0 22px 55px rgb(37 99 235/.25),inset 0 1px 2px rgb(255 255 255/.7);transform:scale(calc(1 + var(--voice-level)*.07));transition:transform 80ms linear,filter .25s ease}.voice-stage.is-muted .voice-orb{filter:saturate(.35)}.voice-orb-layer{position:absolute;width:180%;height:72%;left:-40%;border-radius:45%;mix-blend-mode:screen}.voice-orb-layer-one{top:8%;background:linear-gradient(90deg,rgb(34 211 238/.72),rgb(255 255 255/.82),rgb(129 140 248/.7));transform:rotate(-10deg);animation:mcp-wave-one 5.5s ease-in-out infinite}.voice-orb-layer-two{bottom:-15%;background:linear-gradient(90deg,rgb(30 64 175/.85),rgb(139 92 246/.75),rgb(103 232 249/.8));transform:rotate(13deg);animation:mcp-wave-two 4.8s ease-in-out infinite}.voice-orb-highlight{position:absolute;inset:8px 28px auto;height:36%;border-radius:999px;background:linear-gradient(180deg,rgb(255 255 255/.55),transparent);filter:blur(5px)}.voice-stage.is-speaking .voice-orb{box-shadow:0 24px 70px rgb(79 70 229/.38),inset 0 1px 2px rgb(255 255 255/.75)}
        .voice-control{display:grid;width:54px;height:54px;place-items:center;border-radius:999px;transition:transform .2s ease,background-color .2s ease}.voice-control:not(:disabled):hover{transform:translateY(-2px)}.voice-main-control{display:grid;width:76px;height:76px;place-items:center;border:7px solid white;border-radius:999px;background:linear-gradient(145deg,#3b82f6,#4f46e5);color:white;box-shadow:0 0 0 1px rgb(37 99 235/.12),0 14px 35px rgb(37 99 235/.32);transition:transform .2s ease}.dark .voice-main-control{border-color:rgb(17 24 39)}.voice-main-control:hover{transform:scale(1.04)}.voice-meter-bar{width:3px;border-radius:999px;background:rgb(59 130 246/calc(.25 + var(--voice-level)*.7));transition:height 80ms linear}
        @keyframes mcp-voice-ring{0%{opacity:.45;transform:scale(.72)}75%,100%{opacity:0;transform:scale(1.08)}}@keyframes mcp-wave-one{0%,100%{transform:translateX(-5%) rotate(-10deg) scaleY(.78)}50%{transform:translateX(8%) rotate(7deg) scaleY(1.08)}}@keyframes mcp-wave-two{0%,100%{transform:translateX(8%) rotate(13deg) scaleY(1)}50%{transform:translateX(-6%) rotate(-5deg) scaleY(.72)}}
        @media(max-height:760px){.voice-stage{width:170px;height:170px}.voice-orb{width:118px;height:118px}}@media(prefers-reduced-motion:reduce){.voice-ring,.voice-orb-layer{animation:none!important}.voice-orb{transition:none}}
    </style>

    <script>
        window.mcpVoiceAssistant=(wire)=>({
            mode:'text',isActive:false,isMuted:false,isSpeaking:false,level:0,status:'idle',model:'',inputTranscript:'',outputTranscript:'',errorMessage:'',socket:null,stream:null,audioContext:null,processor:null,source:null,playbackTime:0,playbackSources:[],setupComplete:false,
            get displayTranscript(){return this.outputTranscript||this.inputTranscript},get modelLabel(){return this.model?`Gemini Live · ${this.model}`:'{{ __('mcp::filament/pages/ai-assistant.voice.ready') }}'},
            get statusLabel(){return ({idle:'{{ __('mcp::filament/pages/ai-assistant.voice.idle') }}',connecting:'{{ __('mcp::filament/pages/ai-assistant.voice.connecting') }}',listening:'{{ __('mcp::filament/pages/ai-assistant.voice.listening') }}',thinking:'{{ __('mcp::filament/pages/ai-assistant.voice.thinking') }}',speaking:'{{ __('mcp::filament/pages/ai-assistant.voice.speaking') }}',muted:'{{ __('mcp::filament/pages/ai-assistant.voice.muted') }}',error:'{{ __('mcp::filament/pages/ai-assistant.voice.error') }}'})[this.status]},
            selectTextMode(){this.stopVoice();this.mode='text'},selectVoiceMode(){this.mode='voice';if(!this.isActive)this.startVoice()},
            async startVoice(){if(this.isActive||this.status==='connecting')return;this.status='connecting';this.errorMessage='';this.inputTranscript='';this.outputTranscript='';try{const session=await wire.createVoiceSession();this.model=session.model;await this.openSocket(session)}catch(error){this.fail(error)}},
            async openSocket(session){const endpoint=`wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContentConstrained?access_token=${encodeURIComponent(session.token)}`;this.socket=new WebSocket(endpoint);this.socket.onopen=()=>this.socket.send(JSON.stringify({setup:{model:`models/${session.model}`,generationConfig:{responseModalities:['AUDIO'],speechConfig:{voiceConfig:{prebuiltVoiceConfig:{voiceName:'Kore'}}}},systemInstruction:{parts:[{text:session.system_instruction}]},tools:[{functionDeclarations:session.tools}],inputAudioTranscription:{},outputAudioTranscription:{},contextWindowCompression:{triggerTokens:25000,slidingWindow:{targetTokens:8000}}}}));this.socket.onmessage=async(event)=>{const payload=event.data instanceof Blob?await event.data.text():event.data;this.handleMessage(JSON.parse(payload))};this.socket.onerror=()=>this.fail(new Error('{{ __('mcp::filament/pages/ai-assistant.voice.connection-error') }}'));this.socket.onclose=()=>{if(this.isActive)this.stopVoice(false)}},
            async beginMicrophone(){this.stream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,autoGainControl:true},video:false});this.audioContext=this.audioContext||new(window.AudioContext||window.webkitAudioContext)();await this.audioContext.resume();this.source=this.audioContext.createMediaStreamSource(this.stream);this.processor=this.audioContext.createScriptProcessor(4096,1,1);const silent=this.audioContext.createGain();silent.gain.value=0;this.source.connect(this.processor);this.processor.connect(silent);silent.connect(this.audioContext.destination);this.processor.onaudioprocess=(event)=>{const samples=event.inputBuffer.getChannelData(0);let sum=0;for(let i=0;i<samples.length;i++)sum+=samples[i]*samples[i];this.level=Math.min(1,Math.sqrt(sum/samples.length)*6);if(!this.isActive||this.isMuted||!this.setupComplete||this.socket?.readyState!==WebSocket.OPEN)return;const pcm=this.toPcm16(this.resample(samples,this.audioContext.sampleRate,16000));this.socket.send(JSON.stringify({realtimeInput:{audio:{data:this.toBase64(pcm),mimeType:'audio/pcm;rate=16000'}}}))};this.isActive=true;this.status='listening'},
            async handleMessage(message){if(message.setupComplete){this.setupComplete=true;try{await this.beginMicrophone()}catch(error){this.fail(error)}return}if(message.toolCall)await this.handleToolCall(message.toolCall);const content=message.serverContent;if(!content)return;if(content.interrupted)this.clearPlayback();if(content.inputTranscription?.text){this.inputTranscript+=content.inputTranscription.text;this.status='thinking'}if(content.outputTranscription?.text)this.outputTranscript+=content.outputTranscription.text;for(const part of content.modelTurn?.parts||[]){if(part.inlineData?.data){this.status='speaking';this.isSpeaking=true;this.playPcm(part.inlineData.data)}}if(content.turnComplete){this.isSpeaking=false;this.status=this.isMuted?'muted':'listening';const userText=this.inputTranscript.trim(),assistantText=this.outputTranscript.trim();if(userText||assistantText)await wire.recordVoiceTurn(userText,assistantText);window.setTimeout(()=>{this.inputTranscript='';this.outputTranscript=''},1200)}},
            async handleToolCall(toolCall){this.status='thinking';const responses=[];for(const call of toolCall.functionCalls||[]){try{const result=await wire.executeVoiceTool(call.name,call.args||{});responses.push({name:call.name,id:call.id,response:{result}})}catch(error){responses.push({name:call.name,id:call.id,response:{error:error.message||String(error)}})}}if(this.socket?.readyState===WebSocket.OPEN)this.socket.send(JSON.stringify({toolResponse:{functionResponses:responses}}))},
            toggleMute(){if(!this.isActive)return;this.isMuted=!this.isMuted;this.status=this.isMuted?'muted':'listening'},
            stopVoice(closeSocket=true){if(this.socket?.readyState===WebSocket.OPEN&&this.setupComplete)this.socket.send(JSON.stringify({realtimeInput:{audioStreamEnd:true}}));if(closeSocket)this.socket?.close();this.stream?.getTracks().forEach(track=>track.stop());this.processor?.disconnect();this.source?.disconnect();this.clearPlayback();this.socket=null;this.stream=null;this.processor=null;this.source=null;this.isActive=false;this.isMuted=false;this.isSpeaking=false;this.setupComplete=false;this.level=0;this.status='idle'},
            fail(error){console.error(error);this.errorMessage=error?.message||String(error);this.status='error';this.stopVoice();this.status='error'},
            resample(input,fromRate,toRate){if(fromRate===toRate)return input;const ratio=fromRate/toRate,length=Math.round(input.length/ratio),output=new Float32Array(length);for(let i=0;i<length;i++){const start=Math.round(i*ratio),end=Math.min(input.length,Math.round((i+1)*ratio));let total=0;for(let j=start;j<end;j++)total+=input[j];output[i]=total/Math.max(1,end-start)}return output},
            toPcm16(samples){const pcm=new Int16Array(samples.length);for(let i=0;i<samples.length;i++){const value=Math.max(-1,Math.min(1,samples[i]));pcm[i]=value<0?value*32768:value*32767}return pcm.buffer},
            toBase64(buffer){const bytes=new Uint8Array(buffer);let binary='';for(let i=0;i<bytes.length;i+=0x8000)binary+=String.fromCharCode(...bytes.subarray(i,i+0x8000));return btoa(binary)},
            playPcm(base64){if(!this.audioContext)return;const binary=atob(base64),bytes=new Uint8Array(binary.length);for(let i=0;i<binary.length;i++)bytes[i]=binary.charCodeAt(i);const pcm=new Int16Array(bytes.buffer),audioBuffer=this.audioContext.createBuffer(1,pcm.length,24000),channel=audioBuffer.getChannelData(0);for(let i=0;i<pcm.length;i++)channel[i]=pcm[i]/32768;const source=this.audioContext.createBufferSource();source.buffer=audioBuffer;source.connect(this.audioContext.destination);const start=Math.max(this.audioContext.currentTime,this.playbackTime);source.start(start);this.playbackTime=start+audioBuffer.duration;this.playbackSources.push(source);source.onended=()=>{this.playbackSources=this.playbackSources.filter(item=>item!==source);if(!this.playbackSources.length)this.isSpeaking=false}},
            clearPlayback(){this.playbackSources.forEach(source=>{try{source.stop()}catch(_){}});this.playbackSources=[];this.playbackTime=this.audioContext?.currentTime||0;this.isSpeaking=false}
        });
        document.addEventListener('livewire:initialized',()=>{const scroll=()=>{const element=document.getElementById('mcp-ai-messages');if(element)element.scrollTop=element.scrollHeight};scroll();Livewire.hook('morph.updated',scroll)});
    </script>
</x-filament-panels::page>
