const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
const source=fs.readFileSync('public/system/assets/js/theme.js','utf8');

function setup({stored=null,systemDark=false,blocked=false,chart}={}) {
    const docEvents={},winEvents={},mediaEvents={},storage=new Map();
    if(stored!==null)storage.set('escopo-theme',stored);
    const buttons=[{attributes:{},listeners:{},setAttribute(k,v){this.attributes[k]=v;},addEventListener(k,fn){this.listeners[k]=fn;}}];
    const document={documentElement:{dataset:{},style:{}},querySelectorAll:()=>buttons,addEventListener(k,fn){(docEvents[k]??=[]).push(fn);},dispatchEvent(event){for(const fn of docEvents[event.type]||[])fn(event);}};
    const media={matches:systemDark,addEventListener(k,fn){mediaEvents[k]=fn;}};
    const window={matchMedia:()=>media,addEventListener(k,fn){winEvents[k]=fn;},Chart:chart};
    const localStorage={getItem(k){if(blocked)throw Error('blocked');return storage.get(k)??null;},setItem(k,v){if(blocked)throw Error('blocked');storage.set(k,v);}};
    vm.runInNewContext(source,{window,document,localStorage,Chart:chart,CustomEvent:class{constructor(type,opts){this.type=type;this.detail=opts.detail;}}});
    document.dispatchEvent({type:'DOMContentLoaded'});
    return {document,buttons,storage,media,mediaEvents,winEvents,click:theme=>{if(document.documentElement.dataset.theme!==theme)buttons[0].listeners.click();},toggle:()=>buttons[0].listeners.click()};
}

test('a primeira visita acompanha o tema do sistema operacional',()=>{assert.equal(setup({systemDark:true}).document.documentElement.dataset.theme,'dark');});
test('a preferência salva tem prioridade sobre o sistema operacional',()=>{assert.equal(setup({stored:'light',systemDark:true}).document.documentElement.dataset.theme,'light');});
test('alternar atualiza o tema, a seleção acessível e a preferência persistida',()=>{const s=setup();s.toggle();assert.equal(s.document.documentElement.dataset.theme,'dark');assert.equal(s.buttons[0].attributes['aria-checked'],'true');assert.equal(s.buttons[0].title,'Ativar modo claro');assert.equal(s.storage.get('escopo-theme'),'dark');s.toggle();assert.equal(s.buttons[0].attributes['aria-checked'],'false');assert.equal(s.document.documentElement.style.colorScheme,'light');});
test('o seletor continua funcionando quando o armazenamento está bloqueado',()=>{const s=setup({blocked:true});s.click('dark');assert.equal(s.document.documentElement.dataset.theme,'dark');});
test('uma preferência inválida não impede o carregamento e usa o tema do sistema',()=>{assert.equal(setup({stored:'invalid',systemDark:true}).document.documentElement.dataset.theme,'dark');});
test('a escolha é sincronizada entre abas e sua remoção volta a seguir o sistema',()=>{const s=setup({systemDark:true,stored:'light'});s.winEvents.storage({key:'escopo-theme',newValue:'dark'});assert.equal(s.document.documentElement.dataset.theme,'dark');s.winEvents.storage({key:'escopo-theme',newValue:null});assert.equal(s.document.documentElement.dataset.theme,'dark');});
test('mudanças no sistema operacional respeitam uma escolha explícita do usuário',()=>{const s=setup();s.media.matches=true;s.mediaEvents.change();assert.equal(s.document.documentElement.dataset.theme,'dark');s.click('light');s.mediaEvents.change();assert.equal(s.document.documentElement.dataset.theme,'light');});
test('gráficos já existentes mudam as legendas, eixos e cores sem recarregar',()=>{let updates=0;const chart={options:{plugins:{legend:{labels:{}}},scales:{x:{ticks:{},grid:{}}}},data:{datasets:[{}]},config:{type:'line'},update(){updates++;}};const s=setup({chart:{defaults:{},instances:{one:chart}}});s.click('dark');assert.equal(chart.options.plugins.legend.labels.color,'#b5bfd0');assert.equal(chart.options.scales.x.ticks.color,'#b5bfd0');assert.equal(chart.data.datasets[0].borderColor,'#ff9cbc');assert.ok(updates>0);});
