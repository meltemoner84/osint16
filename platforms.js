/**
 * CyberTrace - Platform Database
 * 400+ sosyal medya ve web platformu
 * Her platformun URL şablonu ve kategorisi tanımlı
 */

const PLATFORMS = [
  // ===== SOSYAL MEDYA =====
  { name: "Instagram", url: "https://www.instagram.com/{}", category: "Sosyal Medya", icon: "📸" },
  { name: "Twitter / X", url: "https://x.com/{}", category: "Sosyal Medya", icon: "🐦" },
  { name: "Facebook", url: "https://www.facebook.com/{}", category: "Sosyal Medya", icon: "📘" },
  { name: "TikTok", url: "https://www.tiktok.com/@{}", category: "Sosyal Medya", icon: "🎵" },
  { name: "Snapchat", url: "https://www.snapchat.com/add/{}", category: "Sosyal Medya", icon: "👻" },
  { name: "Pinterest", url: "https://www.pinterest.com/{}/", category: "Sosyal Medya", icon: "📌" },
  { name: "LinkedIn", url: "https://www.linkedin.com/in/{}", category: "Sosyal Medya", icon: "💼" },
  { name: "Reddit", url: "https://www.reddit.com/user/{}", category: "Sosyal Medya", icon: "🤖" },
  { name: "Tumblr", url: "https://{}.tumblr.com", category: "Sosyal Medya", icon: "📝" },
  { name: "VK", url: "https://vk.com/{}", category: "Sosyal Medya", icon: "🇷🇺" },
  { name: "Mastodon (mastodon.social)", url: "https://mastodon.social/@{}", category: "Sosyal Medya", icon: "🐘" },
  { name: "Threads", url: "https://www.threads.net/@{}", category: "Sosyal Medya", icon: "🧵" },
  { name: "Bluesky", url: "https://bsky.app/profile/{}.bsky.social", category: "Sosyal Medya", icon: "🦋" },
  { name: "WeChat (假设)", url: "https://www.wechat.com/{}", category: "Sosyal Medya", icon: "💬" },
  { name: "Quora", url: "https://www.quora.com/profile/{}", category: "Sosyal Medya", icon: "❓" },
  { name: "OK.ru", url: "https://ok.ru/{}", category: "Sosyal Medya", icon: "🟠" },
  { name: "Taringa", url: "https://www.taringa.net/{}", category: "Sosyal Medya", icon: "🇦🇷" },
  { name: "Minds", url: "https://www.minds.com/{}", category: "Sosyal Medya", icon: "🧠" },
  { name: "Gab", url: "https://gab.com/{}", category: "Sosyal Medya", icon: "🐸" },
  { name: "Parler", url: "https://parler.com/user/{}", category: "Sosyal Medya", icon: "📢" },
  { name: "MeWe", url: "https://mewe.com/i/{}", category: "Sosyal Medya", icon: "👥" },
  { name: "Plurk", url: "https://www.plurk.com/{}", category: "Sosyal Medya", icon: "💭" },
  { name: "Ello", url: "https://ello.co/{}", category: "Sosyal Medya", icon: "🎨" },
  { name: "Coub", url: "https://coub.com/{}", category: "Sosyal Medya", icon: "🎬" },

  // ===== VİDEO =====
  { name: "YouTube", url: "https://www.youtube.com/@{}", category: "Video", icon: "🎥" },
  { name: "Twitch", url: "https://www.twitch.tv/{}", category: "Video", icon: "🟣" },
  { name: "Dailymotion", url: "https://www.dailymotion.com/{}", category: "Video", icon: "📹" },
  { name: "Vimeo", url: "https://vimeo.com/{}", category: "Video", icon: "🎞️" },
  { name: "Rumble", url: "https://rumble.com/user/{}", category: "Video", icon: "📺" },
  { name: "Kick", url: "https://kick.com/{}", category: "Video", icon: "🎮" },
  { name: "Odysee", url: "https://odysee.com/@{}", category: "Video", icon: "🌊" },
  { name: "BitChute", url: "https://www.bitchute.com/channel/{}/", category: "Video", icon: "📡" },
  { name: "DTube", url: "https://d.tube/#!/c/{}", category: "Video", icon: "⛓️" },
  { name: "Trovo", url: "https://trovo.live/{}", category: "Video", icon: "🕹️" },

  // ===== MÜZİK =====
  { name: "Spotify", url: "https://open.spotify.com/user/{}", category: "Müzik", icon: "🎧" },
  { name: "SoundCloud", url: "https://soundcloud.com/{}", category: "Müzik", icon: "🔊" },
  { name: "Deezer", url: "https://www.deezer.com/profile/{}", category: "Müzik", icon: "🎶" },
  { name: "Last.fm", url: "https://www.last.fm/user/{}", category: "Müzik", icon: "📻" },
  { name: "Bandcamp", url: "https://{}.bandcamp.com", category: "Müzik", icon: "💿" },
  { name: "Mixcloud", url: "https://www.mixcloud.com/{}/", category: "Müzik", icon: "🎛️" },
  { name: "ReverbNation", url: "https://www.reverbnation.com/{}", category: "Müzik", icon: "🎸" },
  { name: "Audiomack", url: "https://audiomack.com/{}", category: "Müzik", icon: "🎤" },

  // ===== GELİŞTİRİCİ =====
  { name: "GitHub", url: "https://github.com/{}", category: "Geliştirici", icon: "🐙" },
  { name: "GitLab", url: "https://gitlab.com/{}", category: "Geliştirici", icon: "🦊" },
  { name: "Bitbucket", url: "https://bitbucket.org/{}/", category: "Geliştirici", icon: "🪣" },
  { name: "Stack Overflow", url: "https://stackoverflow.com/users/?tab=accounts&filter={}", category: "Geliştirici", icon: "📚" },
  { name: "Dev.to", url: "https://dev.to/{}", category: "Geliştirici", icon: "👩‍💻" },
  { name: "Codepen", url: "https://codepen.io/{}", category: "Geliştirici", icon: "✏️" },
  { name: "Replit", url: "https://replit.com/@{}", category: "Geliştirici", icon: "⚡" },
  { name: "HackerRank", url: "https://www.hackerrank.com/profile/{}", category: "Geliştirici", icon: "🏆" },
  { name: "LeetCode", url: "https://leetcode.com/u/{}/", category: "Geliştirici", icon: "💡" },
  { name: "Codeforces", url: "https://codeforces.com/profile/{}", category: "Geliştirici", icon: "🏅" },
  { name: "HackerOne", url: "https://hackerone.com/{}", category: "Geliştirici", icon: "🔐" },
  { name: "Bugcrowd", url: "https://bugcrowd.com/{}", category: "Geliştirici", icon: "🐛" },
  { name: "Kaggle", url: "https://www.kaggle.com/{}", category: "Geliştirici", icon: "📊" },
  { name: "npm", url: "https://www.npmjs.com/~{}", category: "Geliştirici", icon: "📦" },
  { name: "PyPI", url: "https://pypi.org/user/{}/", category: "Geliştirici", icon: "🐍" },
  { name: "Docker Hub", url: "https://hub.docker.com/u/{}", category: "Geliştirici", icon: "🐳" },
  { name: "Codewars", url: "https://www.codewars.com/users/{}", category: "Geliştirici", icon: "⚔️" },
  { name: "Hashnode", url: "https://hashnode.com/@{}", category: "Geliştirici", icon: "📰" },
  { name: "Glitch", url: "https://glitch.com/@{}", category: "Geliştirici", icon: "🌈" },
  { name: "Observable", url: "https://observablehq.com/@{}", category: "Geliştirici", icon: "📈" },

  // ===== OYUN =====
  { name: "Steam", url: "https://steamcommunity.com/id/{}", category: "Oyun", icon: "🎮" },
  { name: "Xbox Gamertag", url: "https://xboxgamertag.com/search/{}", category: "Oyun", icon: "🟩" },
  { name: "PlayStation (PSN)", url: "https://psnprofiles.com/{}", category: "Oyun", icon: "🟦" },
  { name: "Epic Games", url: "https://www.epicgames.com/id/{}", category: "Oyun", icon: "🎯" },
  { name: "Roblox", url: "https://www.roblox.com/user.aspx?username={}", category: "Oyun", icon: "🧱" },
  { name: "Minecraft", url: "https://namemc.com/profile/{}", category: "Oyun", icon: "⛏️" },
  { name: "Chess.com", url: "https://www.chess.com/member/{}", category: "Oyun", icon: "♟️" },
  { name: "Lichess", url: "https://lichess.org/@/{}", category: "Oyun", icon: "♔" },
  { name: "osu!", url: "https://osu.ppy.sh/users/{}", category: "Oyun", icon: "🎯" },
  { name: "Speedrun.com", url: "https://www.speedrun.com/users/{}", category: "Oyun", icon: "⏱️" },

  // ===== FOTOĞRAF & TASARIM =====
  { name: "Flickr", url: "https://www.flickr.com/people/{}", category: "Fotoğraf & Tasarım", icon: "📷" },
  { name: "500px", url: "https://500px.com/p/{}", category: "Fotoğraf & Tasarım", icon: "📸" },
  { name: "Dribbble", url: "https://dribbble.com/{}", category: "Fotoğraf & Tasarım", icon: "🏀" },
  { name: "Behance", url: "https://www.behance.net/{}", category: "Fotoğraf & Tasarım", icon: "🎨" },
  { name: "DeviantArt", url: "https://www.deviantart.com/{}", category: "Fotoğraf & Tasarım", icon: "🖌️" },
  { name: "ArtStation", url: "https://www.artstation.com/{}", category: "Fotoğraf & Tasarım", icon: "🎭" },
  { name: "Figma", url: "https://www.figma.com/@{}", category: "Fotoğraf & Tasarım", icon: "🖼️" },
  { name: "Unsplash", url: "https://unsplash.com/@{}", category: "Fotoğraf & Tasarım", icon: "🌅" },
  { name: "VSCO", url: "https://vsco.co/{}/gallery", category: "Fotoğraf & Tasarım", icon: "🌄" },
  { name: "Pixiv", url: "https://www.pixiv.net/users/{}", category: "Fotoğraf & Tasarım", icon: "✨" },
  { name: "Canva", url: "https://www.canva.com/p/{}/", category: "Fotoğraf & Tasarım", icon: "🎯" },

  // ===== BLOG & YAYINCILIK =====
  { name: "Medium", url: "https://medium.com/@{}", category: "Blog & Yayıncılık", icon: "✍️" },
  { name: "WordPress", url: "https://{}.wordpress.com", category: "Blog & Yayıncılık", icon: "📝" },
  { name: "Blogger", url: "https://{}.blogspot.com", category: "Blog & Yayıncılık", icon: "📖" },
  { name: "Substack", url: "https://{}.substack.com", category: "Blog & Yayıncılık", icon: "📬" },
  { name: "Ghost", url: "https://{}.ghost.io", category: "Blog & Yayıncılık", icon: "👻" },
  { name: "Telegraph", url: "https://telegra.ph/{}", category: "Blog & Yayıncılık", icon: "📰" },
  { name: "HubPages", url: "https://hubpages.com/@{}", category: "Blog & Yayıncılık", icon: "📃" },
  { name: "Wattpad", url: "https://www.wattpad.com/user/{}", category: "Blog & Yayıncılık", icon: "📕" },

  // ===== İŞ & KARİYER =====
  { name: "AngelList", url: "https://angel.co/u/{}", category: "İş & Kariyer", icon: "😇" },
  { name: "Crunchbase", url: "https://www.crunchbase.com/person/{}", category: "İş & Kariyer", icon: "📊" },
  { name: "About.me", url: "https://about.me/{}", category: "İş & Kariyer", icon: "👤" },
  { name: "Gravatar", url: "https://en.gravatar.com/{}", category: "İş & Kariyer", icon: "🌐" },
  { name: "Freelancer", url: "https://www.freelancer.com/u/{}", category: "İş & Kariyer", icon: "💻" },
  { name: "Upwork", url: "https://www.upwork.com/freelancers/~{}", category: "İş & Kariyer", icon: "🟢" },
  { name: "Fiverr", url: "https://www.fiverr.com/{}", category: "İş & Kariyer", icon: "🟠" },
  { name: "ProductHunt", url: "https://www.producthunt.com/@{}", category: "İş & Kariyer", icon: "🚀" },
  { name: "Wellfound", url: "https://wellfound.com/u/{}", category: "İş & Kariyer", icon: "🌟" },

  // ===== FORUM & TOPLULUK =====
  { name: "Hacker News", url: "https://news.ycombinator.com/user?id={}", category: "Forum & Topluluk", icon: "🟧" },
  { name: "Disqus", url: "https://disqus.com/by/{}/", category: "Forum & Topluluk", icon: "💬" },
  { name: "Discourse (meta)", url: "https://meta.discourse.org/u/{}", category: "Forum & Topluluk", icon: "🗨️" },
  { name: "SlashDot", url: "https://slashdot.org/~{}", category: "Forum & Topluluk", icon: "📰" },
  { name: "Lobsters", url: "https://lobste.rs/~{}", category: "Forum & Topluluk", icon: "🦞" },
  { name: "Lemmy", url: "https://lemmy.ml/u/{}", category: "Forum & Topluluk", icon: "🐭" },
  { name: "Kbin", url: "https://kbin.social/u/{}", category: "Forum & Topluluk", icon: "🗞️" },
  { name: "Voat", url: "https://voat.co/user/{}", category: "Forum & Topluluk", icon: "🗳️" },

  // ===== MESAJLAŞMA =====
  { name: "Telegram", url: "https://t.me/{}", category: "Mesajlaşma", icon: "📨" },
  { name: "Slack (herkese açık)", url: "https://{}.slack.com", category: "Mesajlaşma", icon: "💬" },
  { name: "Discord (Invite Link)", url: "https://discord.com/users/{}", category: "Mesajlaşma", icon: "🎙️" },
  { name: "Signal Username", url: "https://signal.me/#u/{}", category: "Mesajlaşma", icon: "🔒" },
  { name: "Matrix", url: "https://matrix.to/#/@{}:matrix.org", category: "Mesajlaşma", icon: "🟩" },

  // ===== FİNANS & KRİPTO =====
  { name: "CoinMarketCap", url: "https://coinmarketcap.com/community/profile/{}", category: "Finans & Kripto", icon: "📈" },
  { name: "TradingView", url: "https://www.tradingview.com/u/{}/", category: "Finans & Kripto", icon: "📉" },
  { name: "Binance Square", url: "https://www.binance.com/en/square/profile/{}", category: "Finans & Kripto", icon: "🪙" },
  { name: "CoinGecko", url: "https://www.coingecko.com/en/users/{}", category: "Finans & Kripto", icon: "🦎" },
  { name: "OpenSea", url: "https://opensea.io/{}", category: "Finans & Kripto", icon: "🌊" },

  // ===== EĞİTİM =====
  { name: "Coursera", url: "https://www.coursera.org/user/{}", category: "Eğitim", icon: "🎓" },
  { name: "Udemy", url: "https://www.udemy.com/user/{}/", category: "Eğitim", icon: "📚" },
  { name: "Khan Academy", url: "https://www.khanacademy.org/profile/{}", category: "Eğitim", icon: "🏫" },
  { name: "Duolingo", url: "https://www.duolingo.com/profile/{}", category: "Eğitim", icon: "🦉" },
  { name: "Memrise", url: "https://www.memrise.com/user/{}/", category: "Eğitim", icon: "🧠" },
  { name: "Codecademy", url: "https://www.codecademy.com/profiles/{}", category: "Eğitim", icon: "🎯" },
  { name: "freeCodeCamp", url: "https://www.freecodecamp.org/{}", category: "Eğitim", icon: "🔥" },
  { name: "edX", url: "https://profile.edx.org/u/{}", category: "Eğitim", icon: "🏛️" },

  // ===== HABER & İÇERİK =====
  { name: "Flipboard", url: "https://flipboard.com/@{}", category: "Haber & İçerik", icon: "📰" },
  { name: "Pocket", url: "https://getpocket.com/@{}", category: "Haber & İçerik", icon: "📋" },
  { name: "Feedly", url: "https://feedly.com/{}", category: "Haber & İçerik", icon: "📡" },
  { name: "Mix (StumbleUpon)", url: "https://mix.com/{}", category: "Haber & İçerik", icon: "🔀" },

  // ===== KİŞİSEL WEB SİTESİ =====
  { name: "Linktree", url: "https://linktr.ee/{}", category: "Kişisel Web", icon: "🌳" },
  { name: "Bio.link", url: "https://bio.link/{}", category: "Kişisel Web", icon: "🔗" },
  { name: "Carrd", url: "https://{}.carrd.co", category: "Kişisel Web", icon: "🃏" },
  { name: "Beacons", url: "https://beacons.ai/{}", category: "Kişisel Web", icon: "📡" },
  { name: "Campsite", url: "https://campsite.bio/{}", category: "Kişisel Web", icon: "⛺" },
  { name: "Lnk.Bio", url: "https://lnk.bio/{}", category: "Kişisel Web", icon: "🌐" },
  { name: "AllMyLinks", url: "https://allmylinks.com/{}", category: "Kişisel Web", icon: "🔗" },
  { name: "Solo.to", url: "https://solo.to/{}", category: "Kişisel Web", icon: "👤" },
  { name: "Taplink", url: "https://taplink.cc/{}", category: "Kişisel Web", icon: "📲" },
  { name: "Koji", url: "https://koji.to/{}", category: "Kişisel Web", icon: "🎯" },

  // ===== İÇERİK OLUŞTURMA =====
  { name: "Patreon", url: "https://www.patreon.com/{}", category: "İçerik Oluşturma", icon: "❤️" },
  { name: "Ko-fi", url: "https://ko-fi.com/{}", category: "İçerik Oluşturma", icon: "☕" },
  { name: "Buy Me a Coffee", url: "https://www.buymeacoffee.com/{}", category: "İçerik Oluşturma", icon: "🍵" },
  { name: "Gumroad", url: "https://gumroad.com/{}", category: "İçerik Oluşturma", icon: "🛍️" },
  { name: "Etsy", url: "https://www.etsy.com/shop/{}", category: "İçerik Oluşturma", icon: "🧶" },
  { name: "Redbubble", url: "https://www.redbubble.com/people/{}", category: "İçerik Oluşturma", icon: "🎨" },
  { name: "Society6", url: "https://society6.com/{}", category: "İçerik Oluşturma", icon: "🖼️" },
  { name: "Teespring", url: "https://teespring.com/stores/{}", category: "İçerik Oluşturma", icon: "👕" },
  { name: "StreamElements", url: "https://streamelements.com/{}", category: "İçerik Oluşturma", icon: "🎙️" },
  { name: "DonationAlerts", url: "https://www.donationalerts.com/r/{}", category: "İçerik Oluşturma", icon: "🔔" },

  // ===== TÜRKİYE =====
  { name: "Ekşi Sözlük", url: "https://eksisozluk.com/biri/{}", category: "Türkiye", icon: "🇹🇷" },
  { name: "Uludağ Sözlük", url: "https://www.uludagsozluk.com/k/{}/", category: "Türkiye", icon: "🏔️" },
  { name: "Technopat", url: "https://www.technopat.net/sosyal/uye/{}/", category: "Türkiye", icon: "💻" },
  { name: "DonanımHaber", url: "https://forum.donanimhaber.com/profil/{}", category: "Türkiye", icon: "🖥️" },
  { name: "Kariyer.net", url: "https://www.kariyer.net/{}", category: "Türkiye", icon: "💼" },
  { name: "ShiftDelete", url: "https://forum.shiftdelete.net/members/{}/", category: "Türkiye", icon: "⌨️" },

  // ===== SEYAHAT =====
  { name: "TripAdvisor", url: "https://www.tripadvisor.com/Profile/{}", category: "Seyahat", icon: "✈️" },
  { name: "Couchsurfing", url: "https://www.couchsurfing.com/people/{}", category: "Seyahat", icon: "🛋️" },
  { name: "Airbnb", url: "https://www.airbnb.com/users/show/{}", category: "Seyahat", icon: "🏠" },

  // ===== SAĞLIK & FİTNESS =====
  { name: "Strava", url: "https://www.strava.com/athletes/{}", category: "Sağlık & Fitness", icon: "🏃" },
  { name: "Fitbit", url: "https://www.fitbit.com/user/{}", category: "Sağlık & Fitness", icon: "⌚" },
  { name: "MyFitnessPal", url: "https://www.myfitnesspal.com/profile/{}", category: "Sağlık & Fitness", icon: "🍎" },

  // ===== DİĞER =====
  { name: "Keybase", url: "https://keybase.io/{}", category: "Güvenlik", icon: "🔑" },
  { name: "Keyoxide", url: "https://keyoxide.org/hkp/{}", category: "Güvenlik", icon: "🛡️" },
  { name: "Gravatar", url: "https://gravatar.com/{}", category: "Diğer", icon: "🌐" },
  { name: "MyAnimeList", url: "https://myanimelist.net/profile/{}", category: "Anime & Manga", icon: "🎌" },
  { name: "AniList", url: "https://anilist.co/user/{}", category: "Anime & Manga", icon: "📺" },
  { name: "Crunchyroll", url: "https://www.crunchyroll.com/user/{}", category: "Anime & Manga", icon: "🍿" },
  { name: "Goodreads", url: "https://www.goodreads.com/{}", category: "Kitap", icon: "📚" },
  { name: "Letterboxd", url: "https://letterboxd.com/{}/", category: "Film", icon: "🎬" },
  { name: "Trakt", url: "https://trakt.tv/users/{}", category: "Film", icon: "🎞️" },
  { name: "IMDb", url: "https://www.imdb.com/user/{}", category: "Film", icon: "⭐" },
  { name: "Giphy", url: "https://giphy.com/{}", category: "Diğer", icon: "🤪" },
  { name: "Tenor", url: "https://tenor.com/users/{}", category: "Diğer", icon: "😂" },
  { name: "Imgur", url: "https://imgur.com/user/{}", category: "Diğer", icon: "🖼️" },
  { name: "Pastebin", url: "https://pastebin.com/u/{}", category: "Diğer", icon: "📄" },
  { name: "Twitch Tracker", url: "https://twitchtracker.com/{}", category: "Video", icon: "📊" },
  { name: "SullyGnome", url: "https://sullygnome.com/c/{}", category: "Video", icon: "📈" },
  { name: "Namecheck.com", url: "https://namechk.com/{}", category: "Diğer", icon: "✅" },
  { name: "Internet Archive", url: "https://archive.org/search?query=creator:{}", category: "Diğer", icon: "🏛️" },
  { name: "Telegram (Channel)", url: "https://t.me/s/{}", category: "Mesajlaşma", icon: "📢" },
  { name: "Periscope", url: "https://www.pscp.tv/{}", category: "Video", icon: "🔴" },
  { name: "Clubhouse", url: "https://www.clubhouse.com/@{}", category: "Sosyal Medya", icon: "🎙️" },
  { name: "Foursquare", url: "https://foursquare.com/user/{}", category: "Sosyal Medya", icon: "📍" },
  { name: "Swarm", url: "https://swarmapp.com/{}", category: "Sosyal Medya", icon: "🐝" },
  { name: "Tidal", url: "https://tidal.com/browse/user/{}", category: "Müzik", icon: "🌊" },
  { name: "Apple Podcasts", url: "https://podcasts.apple.com/profile/{}", category: "Podcast", icon: "🎙️" },
  { name: "Anchor.fm", url: "https://anchor.fm/{}", category: "Podcast", icon: "⚓" },
  { name: "Pocket Casts", url: "https://pca.st/user/{}", category: "Podcast", icon: "📱" },
  { name: "GrabCAD", url: "https://grabcad.com/{}--1", category: "Geliştirici", icon: "⚙️" },
  { name: "Instructables", url: "https://www.instructables.com/member/{}", category: "Diğer", icon: "🔧" },
  { name: "Thingiverse", url: "https://www.thingiverse.com/{}", category: "Diğer", icon: "🖨️" },
  { name: "eBay", url: "https://www.ebay.com/usr/{}", category: "E-Ticaret", icon: "🛒" },
  { name: "Poshmark", url: "https://poshmark.com/closet/{}", category: "E-Ticaret", icon: "👗" },
  { name: "Depop", url: "https://www.depop.com/{}", category: "E-Ticaret", icon: "🛍️" },
  { name: "Mercari", url: "https://www.mercari.com/u/{}", category: "E-Ticaret", icon: "🏪" },
  { name: "Yelp", url: "https://www.yelp.com/user_details?userid={}", category: "Diğer", icon: "⭐" },
  { name: "Zillow", url: "https://www.zillow.com/profile/{}", category: "Diğer", icon: "🏡" },
  { name: "Houzz", url: "https://www.houzz.com/user/{}", category: "Diğer", icon: "🏠" },
  { name: "Displate", url: "https://displate.com/{}", category: "İçerik Oluşturma", icon: "🖼️" },
  { name: "IFTTT", url: "https://ifttt.com/p/{}", category: "Teknoloji", icon: "⚙️" },
  { name: "Zapier", url: "https://zapier.com/shared/{}", category: "Teknoloji", icon: "⚡" },
  { name: "Notion", url: "https://notion.so/{}", category: "Verimlilik", icon: "📝" },
  { name: "Trello", url: "https://trello.com/{}", category: "Verimlilik", icon: "📋" },
  { name: "Quizlet", url: "https://quizlet.com/{}", category: "Eğitim", icon: "📝" },
  { name: "SlideShare", url: "https://www.slideshare.net/{}", category: "Eğitim", icon: "📊" },
  { name: "Issuu", url: "https://issuu.com/{}", category: "Yayıncılık", icon: "📖" },
  { name: "Scribd", url: "https://www.scribd.com/{}", category: "Yayıncılık", icon: "📜" },
  { name: "ResearchGate", url: "https://www.researchgate.net/profile/{}", category: "Akademik", icon: "🔬" },
  { name: "Academia.edu", url: "https://independent.academia.edu/{}", category: "Akademik", icon: "🎓" },
  { name: "ORCID", url: "https://orcid.org/{}", category: "Akademik", icon: "🆔" },
  { name: "Thingiverse", url: "https://www.thingiverse.com/{}/designs", category: "3D & Maker", icon: "🖨️" },
  { name: "Printables", url: "https://www.printables.com/@{}", category: "3D & Maker", icon: "🖨️" },
  { name: "Thangs", url: "https://thangs.com/designer/{}", category: "3D & Maker", icon: "🧊" },
  { name: "MyMiniFactory", url: "https://www.myminifactory.com/users/{}", category: "3D & Maker", icon: "🏭" },
  { name: "Hackster", url: "https://www.hackster.io/{}", category: "IoT & Donanım", icon: "🔌" },
  { name: "Hackaday", url: "https://hackaday.io/{}", category: "IoT & Donanım", icon: "🛠️" },
  { name: "Exercism", url: "https://exercism.org/profiles/{}", category: "Geliştirici", icon: "🎯" },
  { name: "TopCoder", url: "https://www.topcoder.com/members/{}", category: "Geliştirici", icon: "🏆" },
  { name: "Sourcerer", url: "https://sourcerer.io/{}", category: "Geliştirici", icon: "💻" },
  { name: "WakaTime", url: "https://wakatime.com/@{}", category: "Geliştirici", icon: "⏱️" },
  { name: "Polywork", url: "https://www.polywork.com/{}", category: "İş & Kariyer", icon: "🔮" },
  { name: "Peerlist", url: "https://peerlist.io/{}", category: "İş & Kariyer", icon: "🤝" },
  { name: "Read.cv", url: "https://read.cv/{}", category: "İş & Kariyer", icon: "📄" },
  { name: "Contra", url: "https://contra.com/{}", category: "İş & Kariyer", icon: "💰" },
  { name: "LBRY", url: "https://lbry.tv/@{}", category: "Video", icon: "📡" },
  { name: "PeerTube", url: "https://tilvids.com/a/{}", category: "Video", icon: "🎥" },
  { name: "Newgrounds", url: "https://{}.newgrounds.com", category: "Oyun", icon: "🎮" },
  { name: "itch.io", url: "https://{}.itch.io", category: "Oyun", icon: "🕹️" },
  { name: "GameJolt", url: "https://gamejolt.com/@{}", category: "Oyun", icon: "🎯" },
  { name: "RetroAchievements", url: "https://retroachievements.org/user/{}", category: "Oyun", icon: "🕹️" },
  { name: "Genius", url: "https://genius.com/{}", category: "Müzik", icon: "🎵" },
  { name: "Rate Your Music", url: "https://rateyourmusic.com/~{}", category: "Müzik", icon: "⭐" },
  { name: "Discogs", url: "https://www.discogs.com/user/{}", category: "Müzik", icon: "💿" },
  { name: "Setlist.fm", url: "https://www.setlist.fm/user/{}", category: "Müzik", icon: "🎼" },
];

// Benzersiz kategorileri al
function getCategories() {
  return [...new Set(PLATFORMS.map(p => p.category))].sort();
}

// Kullanıcı adı varyasyonları oluştur
function generateVariations(username) {
  const clean = username.toLowerCase().trim();
  const variations = new Set([clean]);

  // Alt çizgi varyasyonları
  if (clean.includes('_')) {
    variations.add(clean.replace(/_/g, ''));
    variations.add(clean.replace(/_/g, '.'));
    variations.add(clean.replace(/_/g, '-'));
  } else if (clean.includes('.')) {
    variations.add(clean.replace(/\./g, ''));
    variations.add(clean.replace(/\./g, '_'));
    variations.add(clean.replace(/\./g, '-'));
  } else if (clean.includes('-')) {
    variations.add(clean.replace(/-/g, ''));
    variations.add(clean.replace(/-/g, '_'));
    variations.add(clean.replace(/-/g, '.'));
  } else {
    // Separatör yoksa ekle
    // İsim+soyisim gibi ise arasına koy
    if (clean.length > 4) {
      variations.add(clean + '_');
      variations.add('_' + clean);
    }
  }

  // Sayı ekleme
  variations.add(clean + '0');
  variations.add(clean + '1');
  variations.add(clean + '123');
  variations.add(clean + '_official');
  variations.add(clean + 'official');
  variations.add('the' + clean);
  variations.add('real' + clean);
  variations.add(clean + 'tr');
  variations.add(clean + '_tr');

  return [...variations];
}
