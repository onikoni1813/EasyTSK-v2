/**
 * Shared Offerwall Icons & Brand Presets Utility
 * Provides intelligent icon resolution, network presets, and fallback badges.
 */

export const networkPresets = [
  {
    name: 'Timewall',
    icon: '⏱️',
    logo: 'https://timewall.io/images/logo.png',
    desc: 'Surveys, clicks, micro-tasks and fast reward credits.',
    pattern: 'https://timewall.io/offerwall?user={user_id}',
    ratio: 1.00,
    param_user_id: 'userID',
    param_amount: 'currencyAmount',
    param_transaction_id: 'transactionID',
    param_status: 'type',
    param_secret_key: 'hash',
    status_chargeback_value: 'chargeback'
  },
  {
    name: 'BitLabs',
    icon: '🧪',
    logo: 'https://web.bitlabs.ai/favicon.ico',
    desc: 'High paying global surveys and market research with guaranteed payouts.',
    pattern: 'https://web.bitlabs.ai/?uid={user_id}&token=YOUR_BITLABS_TOKEN',
    ratio: 1.00,
    param_user_id: 'uid',
    param_amount: 'val',
    param_transaction_id: 'tx',
    param_status: 'status',
    param_secret_key: 'hash',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'Notik',
    icon: '⚡',
    logo: 'https://notik.me/assets/img/logo.png',
    desc: 'Top app installs, CPA campaigns, and instant postbacks via Custom Native API & Postback.',
    pattern: 'https://notik.me/coins?api_key=YOUR_API_KEY&pub_id=YOUR_PUB_ID&app_id=YOUR_APP_ID&user_id={user_id}',
    ratio: 1.00,
    is_api: true,
    param_user_id: 'user_id',
    param_amount: 'payout',
    param_transaction_id: 'txn_id',
    param_status: 'status',
    param_secret_key: 'hash',
    status_chargeback_value: '2'
  },
  {
    name: 'Monlix',
    icon: '✨',
    logo: '',
    desc: 'Interactive offerwall with high conversion rates for games and apps.',
    pattern: 'https://surveys.monlix.com/?appId=YOUR_APP_ID&userId={user_id}',
    ratio: 1.00,
    param_user_id: 'userId',
    param_amount: 'rewardValue',
    param_transaction_id: 'transactionId',
    param_status: 'status',
    param_secret_key: 'secretKey',
    status_chargeback_value: 'chargeback'
  },
  {
    name: 'CPX Research',
    icon: '📊',
    logo: '',
    desc: 'Dynamic daily surveys matching user profiles with highest completion rates.',
    pattern: 'https://offers.cpx-research.com/index.php?app_id=YOUR_APP_ID&ext_user_id={user_id}',
    ratio: 1.00,
    param_user_id: 'ext_user_id',
    param_amount: 'amount_local_currency',
    param_transaction_id: 'trans_id',
    param_status: 'status',
    param_secret_key: 'secure_hash',
    status_chargeback_value: '2'
  },
  {
    name: 'Lootably',
    icon: '🎁',
    logo: '',
    desc: 'Watch videos, try games, and complete offers on all devices.',
    pattern: 'https://wall.lootably.com/?placementID=YOUR_PLACEMENT_ID&sid={user_id}',
    ratio: 1.00,
    param_user_id: 'sid',
    param_amount: 'amount',
    param_transaction_id: 'transaction_id',
    param_status: 'status',
    param_secret_key: 'token',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'AdGate Media',
    icon: '🛡️',
    logo: '',
    desc: 'Premium app installs, direct advertiser deals, and quick validation.',
    pattern: 'https://wall.adgaterewards.com/YOUR_WALL_ID/{user_id}',
    ratio: 1.00,
    param_user_id: 's1',
    param_amount: 'points',
    param_transaction_id: 'tx_id',
    param_status: 'status',
    param_secret_key: 'hash',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'AdMaven',
    icon: '🎯',
    logo: 'https://ad-maven.com/wp-content/uploads/2021/08/Admaven-Logo.svg',
    desc: 'Content locker and shortlink monetization integration.',
    pattern: 'https://publishers.ad-maven.com/locker?uid={user_id}',
    ratio: 1.50,
    param_user_id: 'subId',
    param_amount: 'reward',
    param_transaction_id: 'transId',
    param_status: 'status',
    param_secret_key: 'secure',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'Capsbit',
    icon: '💎',
    logo: 'https://Capsbit.com/assets/creatives/main_logo_bg.png',
    desc: 'Capsbit Media offerwall and app install campaigns.',
    pattern: 'https://offerwall.capsbit.com/YOUR_API_KEY/{user_id}',
    ratio: 1.00,
    param_user_id: 'uid',
    param_amount: 'payout',
    param_transaction_id: 'txid',
    param_status: 'status',
    param_secret_key: 'sig',
    status_chargeback_value: 'rejected'
  },
  {
    name: 'EarnWall',
    icon: '💰',
    logo: 'https://earnwall.net/assets/img/offerwall-logo.png',
    desc: 'EarnWall incentivized tasks, surveys and offerwalls.',
    pattern: 'https://earnwall.net/offerwall/YOUR_API_KEY/{user_id}',
    ratio: 1.00,
    param_user_id: 'subId',
    param_amount: 'reward',
    param_transaction_id: 'transId',
    param_status: 'status',
    param_secret_key: 'signature',
    status_chargeback_value: '2'
  },
  {
    name: 'Offerwall.me',
    icon: '🌐',
    logo: 'https://offerwall.me/offerwall-mark.svg',
    desc: 'Offerwall.me PTC Ads, Shortlinks, Tasks, and Surveys with instant postbacks and HMAC signed identity.',
    pattern: 'https://offerwall.me/offerwall/YOUR_API_KEY/{user_id}',
    ratio: 1.00,
    param_user_id: 'subId',
    param_amount: 'reward',
    param_transaction_id: 'transId',
    param_status: 'status',
    param_secret_key: 'signature',
    status_chargeback_value: '2',
    allowed_ips: '95.216.65.163, 2a01:4f9:2b:1dc::2'
  },
  {
    name: 'MoneyRain',
    icon: '🌧️',
    logo: 'https://offerwall.moneyrain.top/assets/favicon-rain-v1.png',
    desc: 'MoneyRain Offerwall with PTC ads and hourly captchas.',
    pattern: 'https://offerwall.moneyrain.top/s/YOUR_SITE_KEY?external_uid={user_id}',
    ratio: 1.00,
    param_user_id: 'external_uid',
    param_amount: 'reward_currency_amount',
    param_transaction_id: 'view_id',
    param_status: 'status',
    param_secret_key: 'signature',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'Wannads',
    icon: '🚀',
    logo: '',
    desc: 'Surveys and app campaigns with instant user rewards.',
    pattern: 'https://api.wannads.com/wall?apiKey=YOUR_KEY&userId={user_id}',
    ratio: 1.00,
    param_user_id: 'subId',
    param_amount: 'reward',
    param_transaction_id: 'transId',
    param_status: 'status',
    param_secret_key: 'signature',
    status_chargeback_value: '2'
  },
  {
    name: 'AyeT-Studios',
    icon: '🎮',
    logo: '',
    desc: 'High paying gaming leveling and progression campaigns.',
    pattern: 'https://www.ayetstudios.com/offers/web_offerwall/YOUR_ID?external_identifier={user_id}',
    ratio: 1.00,
    param_user_id: 'external_identifier',
    param_amount: 'currency_amount',
    param_transaction_id: 'transaction_id',
    param_status: 'status',
    param_secret_key: 'sig',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'Revlum',
    icon: '💠',
    logo: '',
    desc: 'Fast loading next-gen CPA offerwall.',
    pattern: 'https://revlum.com/offerwall?apiKey=YOUR_KEY&userId={user_id}',
    ratio: 1.00,
    param_user_id: 'subId',
    param_amount: 'reward',
    param_transaction_id: 'transId',
    param_status: 'status',
    param_secret_key: 'secure',
    status_chargeback_value: 'reversed'
  },
  {
    name: 'MM Wall',
    icon: '🔥',
    logo: '',
    desc: 'Trending tasks, quick offers and video rewards.',
    pattern: 'https://mmwall.net/wall?api=YOUR_KEY&user={user_id}',
    ratio: 1.00,
    param_user_id: 'user',
    param_amount: 'amount',
    param_transaction_id: 'id',
    param_status: 'status',
    param_secret_key: 'hash',
    status_chargeback_value: 'reversed'
  }
];

export const badgePresets = [
  { icon: '💎', label: 'Diamond' },
  { icon: '⚡', label: 'Flash Bolt' },
  { icon: '🏆', label: 'Trophy' },
  { icon: '💰', label: 'Money Bag' },
  { icon: '🎯', label: 'Target' },
  { icon: '🎮', label: 'Gaming' },
  { icon: '🚀', label: 'Rocket' },
  { icon: '🎁', label: 'Gift Box' },
  { icon: '👑', label: 'Crown' },
  { icon: '🔥', label: 'Hot Fire' },
  { icon: '🌟', label: 'Star' },
  { icon: '🛡️', label: 'Shield' },
  { icon: '📱', label: 'Mobile' },
  { icon: '🌐', label: 'Global' },
  { icon: '📊', label: 'Surveys' },
  { icon: '💸', label: 'Cash Flying' },
  { icon: '⏱️', label: 'Timer' },
  { icon: '🧠', label: 'Brain Quiz' },
  { icon: '🧩', label: 'Puzzle' },
  { icon: '🪙', label: 'Coin' },
  { icon: '🥇', label: 'Gold Medal' },
  { icon: '🎪', label: 'Arcade' },
  { icon: '🍀', label: 'Lucky' },
  { icon: '✨', label: 'Sparkles' }
];

export const networkIconMap = {
  timewall: { icon: '⏱️', bg: 'from-cyan-500/20 to-blue-600/30 border-cyan-500/40 text-cyan-300' },
  notik: { icon: '⚡', bg: 'from-amber-500/20 to-orange-600/30 border-amber-500/40 text-amber-300' },
  bitlabs: { icon: '🧪', bg: 'from-emerald-500/20 to-teal-600/30 border-emerald-500/40 text-emerald-300' },
  monlix: { icon: '✨', bg: 'from-purple-500/20 to-pink-600/30 border-purple-500/40 text-purple-300' },
  cpx: { icon: '📊', bg: 'from-blue-500/20 to-indigo-600/30 border-blue-500/40 text-blue-300' },
  lootably: { icon: '🎁', bg: 'from-rose-500/20 to-pink-600/30 border-rose-500/40 text-rose-300' },
  adgate: { icon: '🛡️', bg: 'from-indigo-500/20 to-violet-600/30 border-indigo-500/40 text-indigo-300' },
  admaven: { icon: '🎯', bg: 'from-red-500/20 to-amber-600/30 border-red-500/40 text-red-300' },
  capsbit: { icon: '💎', bg: 'from-sky-500/20 to-blue-600/30 border-sky-500/40 text-sky-300' },
  earnwall: { icon: '💰', bg: 'from-emerald-500/20 to-green-600/30 border-emerald-500/40 text-emerald-300' },
  moneyrain: { icon: '🌧️', bg: 'from-cyan-500/20 to-teal-600/30 border-cyan-500/40 text-cyan-300' },
  wannads: { icon: '🚀', bg: 'from-orange-500/20 to-amber-600/30 border-orange-500/40 text-orange-300' },
  ayet: { icon: '🎮', bg: 'from-violet-500/20 to-fuchsia-600/30 border-violet-500/40 text-violet-300' },
  hangmyads: { icon: '📢', bg: 'from-pink-500/20 to-rose-600/30 border-pink-500/40 text-pink-300' },
  revlum: { icon: '💠', bg: 'from-teal-500/20 to-cyan-600/30 border-teal-500/40 text-teal-300' },
  mmwall: { icon: '🔥', bg: 'from-orange-500/20 to-red-600/30 border-orange-500/40 text-orange-300' },
  inbrain: { icon: '🧠', bg: 'from-fuchsia-500/20 to-purple-600/30 border-fuchsia-500/40 text-fuchsia-300' },
  tapresearch: { icon: '📱', bg: 'from-indigo-500/20 to-blue-600/30 border-indigo-500/40 text-indigo-300' },
};

export function isEmojiIcon(val) {
  if (!val) return false;
  const str = String(val).trim();
  if (str.startsWith('emoji:')) return true;
  if (str.startsWith('http://') || str.startsWith('https://') || str.startsWith('/') || str.includes('.')) {
    return false;
  }
  return str.length <= 8;
}

export function cleanEmoji(val) {
  if (!val) return '🏆';
  return String(val).replace(/^emoji:/, '').trim();
}

export function isHttpUrl(val) {
  if (!val) return false;
  const str = String(val).trim();
  return /^https?:\/\//i.test(str) || (str.startsWith('/') && str.includes('.'));
}

export function getNetworkFallbackIcon(name) {
  if (!name) return '🏆';
  const clean = String(name).toLowerCase().replace(/[^a-z0-9]/g, '');
  for (const key in networkIconMap) {
    if (clean.includes(key)) {
      return networkIconMap[key].icon;
    }
  }
  return '🏆';
}

export function getIconWrapperClass(ow) {
  if (!ow) return 'bg-slate-900 border-white/10';
  const clean = String(ow.name || '').toLowerCase().replace(/[^a-z0-9]/g, '');
  for (const key in networkIconMap) {
    if (clean.includes(key)) {
      return `bg-gradient-to-br ${networkIconMap[key].bg}`;
    }
  }
  return 'bg-gradient-to-br from-slate-900 to-indigo-950/60 border-white/10';
}
