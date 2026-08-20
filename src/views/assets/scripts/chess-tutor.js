import { Chess } from '/_aml/chess-core.js';

const glyphs = { wk:'♔',wq:'♕',wr:'♖',wb:'♗',wn:'♘',wp:'♙',bk:'♚',bq:'♛',br:'♜',bb:'♝',bn:'♞',bp:'♟' };
let game = new Chess(), selected = null, legalTargets = [], lastMove = null;
let lessonId = null, busy = false, authMode = 'login', user = null, dragSource = null;
let stockfishWorker = null, stockfishReady = null, stockfishSearch = Promise.resolve();
const $ = selector => document.querySelector(selector);
const csrf = () => $('meta[name="csrf-token"]')?.content || '';
const uci = move => `${move.from}${move.to}${move.promotion || ''}`;
let locale = localStorage.getItem('tutor-locale') === 'fr' ? 'fr' : 'en';
const french = {
  'Train':'Jouer','Lessons':'Leçons','Sign in':'Connexion','Start training':'Commencer',
  'PERSONAL CHESS MENTOR':'MENTOR PERSONNEL AUX ÉCHECS','Play a move. Understand the idea.':'Jouez un coup. Comprenez l’idée.',
  'Tutor reviews every decision, answers on the board and saves the lesson so your next game starts smarter.':'Tutor analyse chaque décision, répond sur l’échiquier et conserve la leçon pour améliorer votre prochaine partie.',
  'Tutor online':'Tutor en ligne','“Develop with purpose. I’ll explain the position after every move.”':'« Développez avec intention. Je vous expliquerai la position après chaque coup. »',
  'Play naturally':'Jouez naturellement','Real chess rules, legal moves and fluid piece movement.':'Règles réelles, coups légaux et déplacements fluides.',
  'Learn immediately':'Apprenez immédiatement','Tutor evaluates the idea while the position is still fresh.':'Tutor évalue l’idée pendant que la position est encore fraîche.',
  'Build a record':'Construisez votre parcours','Every explanation becomes part of your private lesson history.':'Chaque explication rejoint votre historique privé de leçons.',
  'LIVE TRAINING':'ENTRAÎNEMENT EN DIRECT','Your board. Your coach. One focused session.':'Votre échiquier. Votre coach. Une séance ciblée.',
  'Play White and let Tutor challenge every decision in real time.':'Jouez les Blancs et laissez Tutor analyser chaque décision en temps réel.',
  'You play White':'Vous jouez les Blancs','Select a piece, then its destination.':'Sélectionnez une pièce, puis sa destination.',
  'DeepSeek chess mentor':'Mentor d’échecs DeepSeek','Ready':'Prêt','Make your first move. I will evaluate the idea, reply, and explain what to learn from it.':'Jouez votre premier coup. J’évaluerai l’idée, répondrai et expliquerai ce qu’il faut retenir.',
  'Tip: fight for the centre and develop a new piece early.':'Conseil : contrôlez le centre et développez rapidement une nouvelle pièce.',
  'Lesson score':'Score de la leçon','Moves reviewed':'Coups analysés','New lesson':'Nouvelle leçon',
  'YOUR PROGRESS':'VOTRE PROGRESSION','Every game becomes a lesson.':'Chaque partie devient une leçon.','Your recent sessions appear here after you sign in.':'Vos séances récentes apparaissent ici après votre connexion.',
  'Sign in to see your saved lessons.':'Connectez-vous pour voir vos leçons enregistrées.','WELCOME TO TUTOR':'BIENVENUE SUR TUTOR','Save every lesson':'Conservez chaque leçon',
  'Create an account or continue where you stopped.':'Créez un compte ou reprenez là où vous vous êtes arrêté.','Create account':'Créer un compte','Name':'Nom','Email':'Courriel','Password':'Mot de passe',
  'Light':'Clair','Dark':'Sombre','System':'Système','Your first lesson will appear here.':'Votre première leçon apparaîtra ici.'
};
const tr = text => locale === 'fr' ? (french[text] || text) : text;

function stockfish() {
  if (stockfishReady) return stockfishReady;
  stockfishReady = new Promise((resolve, reject) => {
    const worker = new Worker('/_aml/stockfish.js#/_aml/stockfish.wasm,worker');
    stockfishWorker = worker;
    const timeout = setTimeout(() => reject(new Error(tr('The chess engine could not start.'))), 20000);
    const readyListener = event => {
      if (String(event.data).trim() !== 'uciok') return;
      worker.removeEventListener('message', readyListener);
      worker.postMessage('setoption name Skill Level value 20');
      worker.postMessage('setoption name Hash value 64');
      worker.postMessage('isready');
      const isReady = readyEvent => {
        if (String(readyEvent.data).trim() !== 'readyok') return;
        worker.removeEventListener('message', isReady);
        clearTimeout(timeout);
        resolve(worker);
      };
      worker.addEventListener('message', isReady);
    };
    worker.addEventListener('message', readyListener);
    worker.addEventListener('error', () => reject(new Error(tr('The chess engine is unavailable.'))), {once:true});
    worker.postMessage('uci');
  });
  return stockfishReady;
}

function calculateStrongestReply(fen) {
  const search = async () => {
    const worker = await stockfish();
    return new Promise((resolve, reject) => {
      let depth = 0, score = null, pv = [];
      const timeout = setTimeout(() => { worker.postMessage('stop'); reject(new Error(tr('The chess engine took too long.'))); }, 15000);
      const listener = event => {
        const line = String(event.data).trim();
        if (line.startsWith('info ')) {
          const depthMatch = line.match(/\bdepth (\d+)/), scoreMatch = line.match(/\bscore (cp|mate) (-?\d+)/), pvMatch = line.match(/\bpv (.+)$/);
          if (depthMatch) depth = Number(depthMatch[1]);
          if (scoreMatch) score = {type:scoreMatch[1], value:Number(scoreMatch[2])};
          if (pvMatch) pv = pvMatch[1].split(/\s+/).slice(0, 12);
          return;
        }
        const match = line.match(/^bestmove\s+([a-h][1-8][a-h][1-8][qrbn]?)/);
        if (!match) return;
        clearTimeout(timeout); worker.removeEventListener('message', listener);
        resolve({move:match[1], depth, score, pv});
      };
      worker.addEventListener('message', listener);
      worker.postMessage('ucinewgame');
      worker.postMessage(`position fen ${fen}`);
      worker.postMessage('go movetime 1800 depth 22');
    });
  };
  const result = stockfishSearch.then(search, search);
  stockfishSearch = result.catch(() => undefined);
  return result;
}
function translateStatic(){
  document.documentElement.lang=locale;
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let node;
  while((node=walker.nextNode())){const value=node.nodeValue.trim();if(!value)continue;if(node._tutorEnglish===undefined)node._tutorEnglish=value;node.nodeValue=node.nodeValue.replace(value,tr(node._tutorEnglish));}
  document.querySelectorAll('[data-locale]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.locale===locale)));
  syncAccount();authTab(authMode);updateBoardStatus();loadLessons();
}

function render() {
  const board = $('#chess-board'); if (!board) return; board.innerHTML = '';
  const checkedKing = game.inCheck() ? game.board().flat().find(piece => piece?.type === 'k' && piece.color === game.turn())?.square : null;
  game.board().forEach((rank,row) => rank.forEach((piece,column) => {
    const file='abcdefgh'[column], rankNumber=8-row, name=`${file}${rankNumber}`;
    const square=document.createElement('button'); square.type='button';
    square.className=`chess-square ${(row+column)%2?'dark':'light'}`; square.dataset.square=name;
    square.setAttribute('aria-label',`${name} ${piece?`${piece.color==='w'?'white':'black'} ${piece.type}`:'empty'}`);
    if(selected===name)square.classList.add('selected');
    const target=legalTargets.find(move=>move.to===name); if(target)square.classList.add(target.captured?'capture-target':'target');
    if(lastMove?.includes(name))square.classList.add('last-move'); if(checkedKing===name)square.classList.add('in-check');
    if(column===0)square.dataset.rank=String(rankNumber); if(row===7)square.dataset.file=file;
    if(piece){const token=document.createElement('span');token.className=`chess-piece ${piece.color==='w'?'white-piece':'black-piece'}`;token.textContent=glyphs[`${piece.color}${piece.type}`];token.draggable=piece.color==='w'&&game.turn()==='w'&&!busy;token.addEventListener('dragstart',event=>startDrag(event,name));token.addEventListener('dragend',endDrag);square.append(token);}
    square.addEventListener('click',()=>chooseSquare(name)); square.addEventListener('dragover',event=>dragOver(event,name));
    square.addEventListener('dragleave',event=>event.currentTarget.classList.remove('drag-over')); square.addEventListener('drop',event=>dropPiece(event,name)); board.append(square);
  })); updateBoardStatus();
}

function selectPiece(square){const piece=game.get(square);if(!piece||piece.color!=='w'||game.turn()!=='w'||busy)return false;selected=square;legalTargets=game.moves({square,verbose:true});render();return true;}
function chooseSquare(square){if(busy||game.isGameOver())return;const move=legalTargets.find(candidate=>candidate.to===square);if(selected&&move){playStudentMove(selected,square);return;}if(!selectPiece(square)){selected=null;legalTargets=[];render();}}
function startDrag(event,square){if(!selectPiece(square)){event.preventDefault();return;}dragSource=square;event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain',square);requestAnimationFrame(()=>event.target.classList.add('is-dragging'));$('#chess-board')?.classList.add('is-dragging-piece');}
function dragOver(event,square){if(!legalTargets.some(move=>move.to===square))return;event.preventDefault();event.dataTransfer.dropEffect='move';event.currentTarget.classList.add('drag-over');}
function dropPiece(event,destination){event.preventDefault();event.currentTarget.classList.remove('drag-over');if(dragSource&&legalTargets.some(move=>move.to===destination))playStudentMove(dragSource,destination);endDrag();}
function endDrag(){document.querySelectorAll('.is-dragging,.drag-over').forEach(node=>node.classList.remove('is-dragging','drag-over'));$('#chess-board')?.classList.remove('is-dragging-piece');dragSource=null;}

async function playStudentMove(from,to){const candidate=legalTargets.find(move=>move.to===to),promotion=candidate?.flags?.includes('p')?'q':undefined,previousPosition=game.fen(),candidateMoves=game.moves({verbose:true}).map(uci);let move;try{move=game.move({from,to,promotion});}catch{return;}lastMove=[from,to];selected=null;legalTargets=[];render();await reviewMove(uci(move),move.san,previousPosition,candidateMoves);}
async function animateTutorMove(code){const from=code.slice(0,2),to=code.slice(2,4),source=document.querySelector(`[data-square="${from}"] .chess-piece`),destination=document.querySelector(`[data-square="${to}"]`);if(source&&destination){const a=source.getBoundingClientRect(),b=destination.getBoundingClientRect(),ghost=source.cloneNode(true),sourceStyle=getComputedStyle(source);ghost.classList.add('moving-piece');Object.assign(ghost.style,{left:`${a.left}px`,top:`${a.top}px`,width:`${a.width}px`,height:`${a.height}px`,fontSize:sourceStyle.fontSize});document.body.append(ghost);source.style.opacity='0';requestAnimationFrame(()=>ghost.style.transform=`translate(${b.left-a.left}px, ${b.top-a.top}px)`);await new Promise(resolve=>setTimeout(resolve,330));ghost.remove();}game.move({from,to,promotion:code[4]||undefined});lastMove=[from,to];render();}
async function api(path,options={}){const headers={Accept:'application/json',...(options.body?{'Content-Type':'application/json','X-CSRF-Token':csrf()}:{}),...(options.headers||{})};const response=await fetch(path,{credentials:'include',...options,headers});const data=await response.json().catch(()=>({error:'Unexpected server response.'}));if(!response.ok)throw new Error(data.error||'Request failed.');return data;}

async function reviewMove(moveCode,san,previousPosition,candidateMoves){if(!user){openAuth();resetBoard();return;}busy=true;render();setTutor(locale==='fr'?'Calcul de grand maître…':'Grandmaster calculation…',locale==='fr'?`Stockfish cherche la réponse la plus forte à ${san}, puis DeepSeek prépare la leçon.`:`Stockfish is finding the strongest reply to ${san}, then DeepSeek prepares the lesson.`,locale==='fr'?'Calcul tactique profond, puis explication pédagogique.':'Deep tactical calculation followed by a teaching explanation.');try{if(!lessonId){const started=await api('/api/lessons',{method:'POST',body:JSON.stringify({position:previousPosition,locale})});lessonId=started.lesson.id;}const replies=game.moves({verbose:true}).map(uci);if(!replies.length){finishGame();return;}const engine=await calculateStrongestReply(game.fen());if(!replies.includes(engine.move))throw new Error(locale==='fr'?'Le moteur a retourné un coup invalide.':'The engine returned an invalid move.');const data=await api('/api/tutor/move',{method:'POST',body:JSON.stringify({lessonId,move:moveCode,previousPosition,position:game.fen(),candidateMoves,legalReplies:replies,engineReply:engine.move,engineDepth:engine.depth,engineScore:engine.score,enginePv:engine.pv,locale})});await animateTutorMove(data.review.reply);$('#lesson-score').textContent=data.lesson.score;$('#move-count').textContent=data.lesson.moves.length;const qualities=locale==='fr'?{excellent:'Excellent',good:'Bon coup',inaccuracy:'Imprécision',mistake:'Erreur'}:{excellent:'Excellent',good:'Good move',inaccuracy:'Inaccuracy',mistake:'Mistake'};const ownMoveLabel=locale==='fr'?`Pourquoi Tutor joue ${data.review.reply}`:`Why Tutor plays ${data.review.reply}`;const memoryLabel=locale==='fr'?'Mémoire':'Memory';const memoryLine=data.review.memoryNote?`\n\n${memoryLabel}: ${data.review.memoryNote}`:'';const completeExplanation=`${data.review.explanation}\n\n${ownMoveLabel}: ${data.review.replyExplanation}${memoryLine}\n\n♞ ${data.review.banter}`;setTutor(qualities[data.review.quality]||(locale==='fr'?'Analysé':'Reviewed'),completeExplanation,data.review.tip);loadLessons();if(game.isGameOver())finishGame();}catch(error){setTutor(locale==='fr'?'Indisponible':'Unavailable',error.message,locale==='fr'?'La position a été restaurée pour préserver la cohérence de la leçon.':'The position was restored so the saved lesson remains consistent.');game.undo();lastMove=null;render();}finally{busy=false;render();}}
function finishGame(){const message=game.isCheckmate()?'Checkmate. The lesson is complete.':game.isDraw()?'Draw. The lesson is complete.':'The game is complete.';setTutor('Game over',message,'Review the saved lesson before starting another game.');}
function updateBoardStatus(){const status=$('#board-status');if(!status)return;if(locale==='fr'){if(busy)status.textContent='Tutor réfléchit…';else if(game.isCheckmate())status.textContent=`Échec et mat · ${game.turn()==='w'?'Les Noirs':'Les Blancs'} gagnent`;else if(game.isDraw())status.textContent='Partie nulle';else if(game.inCheck())status.textContent=`${game.turn()==='w'?'Les Blancs':'Les Noirs'} sont en échec`;else status.textContent=game.turn()==='w'?'À vous · glissez ou sélectionnez une pièce':'Au tour de Tutor';return;}if(busy)status.textContent='Tutor is thinking…';else if(game.isCheckmate())status.textContent=`Checkmate · ${game.turn()==='w'?'Black':'White'} wins`;else if(game.isDraw())status.textContent='Draw';else if(game.inCheck())status.textContent=`${game.turn()==='w'?'White':'Black'} is in check`;else status.textContent=game.turn()==='w'?'Your turn · drag or select a piece':'Tutor’s turn';}
function setTutor(state,message,tip){$('#tutor-state').textContent=state;$('#tutor-message').textContent=message;$('#tutor-tip').textContent=tip;}
function resetBoard(){game=new Chess();selected=null;legalTargets=[];lastMove=null;lessonId=null;$('#lesson-score').textContent='—';$('#move-count').textContent='0';setTutor(tr('Ready'),tr('Make your first move. I will evaluate the idea, reply, and explain what to learn from it.'),tr('Tip: fight for the centre and develop a new piece early.'));render();}

function openAuth(){const modal=$('#auth-modal');modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');}
function closeAuth(){const modal=$('#auth-modal');modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');}
function authTab(mode){authMode=mode;document.querySelectorAll('[data-auth-tab]').forEach(tab=>tab.classList.toggle('is-active',tab.dataset.authTab===mode));document.querySelectorAll('[data-register-only]').forEach(field=>field.hidden=mode!=='register');$('#auth-submit').textContent=tr(mode==='login'?'Sign in':'Create account');$('#auth-error').textContent='';}
async function submitAuth(event){event.preventDefault();const payload=Object.fromEntries(new FormData(event.currentTarget));$('#auth-submit').disabled=true;try{const data=await api(`/api/auth/${authMode}`,{method:'POST',body:JSON.stringify(payload)});user=data.user;closeAuth();syncAccount();await loadLessons();}catch(error){$('#auth-error').textContent=error.message;}finally{$('#auth-submit').disabled=false;}}
function syncAccount(){const button=$('#account-button');button.textContent=user?user.name:tr('Sign in');button.dataset.action=user?'logout':'signin';}
async function loadLessons(){if(!user){$('#lesson-list').innerHTML=`<p class="empty-state">${tr('Sign in to see your saved lessons.')}</p>`;return;}try{const data=await api('/api/lessons');$('#lesson-list').innerHTML=data.lessons.length?data.lessons.map(item=>`<article class="lesson-card"><div><strong>${escapeHtml(item.title)}</strong><span>${item.moves.length} ${locale==='fr'?'coups analysés':'moves reviewed'}</span></div><b>${item.score||'—'}</b></article>`).join(''):`<p class="empty-state">${tr('Your first lesson will appear here.')}</p>`;}catch(error){$('#lesson-list').innerHTML=`<p class="empty-state">${escapeHtml(error.message)}</p>`;}}
const escapeHtml=value=>String(value).replace(/[&<>'"]/g,character=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
document.addEventListener('click',async event=>{const localeButton=event.target.closest('[data-locale]');if(localeButton){locale=localeButton.dataset.locale==='fr'?'fr':'en';localStorage.setItem('tutor-locale',locale);translateStatic();return;}const action=event.target.closest('[data-action]')?.dataset.action;if(!action)return;if(action==='signin')openAuth();if(action==='close-auth')closeAuth();if(action==='start'){$('#training').scrollIntoView({behavior:'smooth'});if(!user)openAuth();}if(action==='new-game')resetBoard();if(action==='logout'){await api('/api/auth/logout',{method:'POST',body:'{}'});user=null;syncAccount();loadLessons();resetBoard();}});
document.querySelectorAll('[data-auth-tab]').forEach(tab=>tab.addEventListener('click',()=>authTab(tab.dataset.authTab)));$('#auth-form')?.addEventListener('submit',submitAuth);api('/api/auth/me').then(data=>{user=data.authenticated?data.user:null;syncAccount();loadLessons();}).catch(()=>{});authTab('login');translateStatic();render();
