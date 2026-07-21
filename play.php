<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>Multi MPD Player with Clear Keys</title>
    <link rel="stylesheet" type="text/css" href="https://b4uplay.com/clpr.css" />
   <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #000;
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: Arial, sans-serif;
        }
        .player-container { 
            width: 100%; 
            height: 100vh; 
            position: relative; 
            z-index: 1; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        /* Player Switcher UI Style */
        .player-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 999;
            background: rgba(0, 0, 0, 0.75);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: opacity 0.4s ease, visibility 0.4s ease;
            opacity: 1;
            visibility: visible;
        }
        .player-switcher.hidden {
            opacity: 0;
            visibility: hidden;
        }
        .player-switcher label {
            color: #fff;
            font-size: 13px;
            margin-right: 5px;
            text-shadow: 1px 1px 2px #000;
        }
        .player-switcher select {
            background: #222;
            color: #fff;
            border: 1px solid #555;
            padding: 4px;
            border-radius: 4px;
            cursor: pointer;
            outline: none;
        }

        /* 📱 മൊബൈൽ സ്ക്രീനുകളിൽ ബോക്സിന്റെ സൈസ് കുറക്കാനുള്ള മീഡിയ ക്വറി */
        @media (max-width: 768px) {
            .player-switcher {
                top: 10px;
                right: 10px;
                padding: 5px 8px;
                border-radius: 4px;
            }
            .player-switcher label {
                font-size: 11px;
                margin-right: 4px;
            }
            .player-switcher select {
                font-size: 11px;
                padding: 2px 4px;
            }
        }

        .player-view {
            width: 100% !important; 
            height: 100% !important;
            display: none;
        }
        .player-view.active {
            display: block;
        }

        /* 🚀 വീഡിയോ സ്ക്രീനിൽ ബ്ലാക്ക് ബോർഡറുകൾ ഇല്ലാതെ ഫിറ്റ് ആക്കാനുള്ള CSS */
        video {
            width: 100% !important;
            height: 100% !important;
            object-fit: fill !important; 
            background-color: #000;
        }

        #jw-player, #clappr-player { width: 100% !important; height: 100% !important; position: absolute; }
        #clappr-player [data-player] { width: 100% !important; height: 100% !important; }
        
        .jwplayer { pointer-events: auto !important; }
        .jw-display-icon-container { pointer-events: none; }
</style>

    <script src="https://cdn.jsdelivr.net/npm/clappr@latest/dist/clappr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dash-shaka-playback@latest/dist/dash-shaka-playback.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/level-selector@0.2.0/dist/level-selector.min.js"></script>
    <script disable-devtool-auto src="https://cdn.jsdelivr.net/npm/disable-devtool@latest"></script>
    <script src="https://ssl.p.jwpcdn.com/player/v/8.48.3/jwplayer.js"></script>
    <script>jwplayer.key = "jTL7dlu7ybUI5NZnDdVgb1laM8/Hj3ftIJ5Vqg==";</script>
</head>
<body>

<div id="switcher-menu" class="player-switcher">
    <label for="choose-player">Change Player:</label>
    <select id="choose-player">
        <option value="jw">JW Player</option>
        <option value="clappr">Clappr Player</option>
    </select>
</div>

<div id="main-container" class="player-container">
    <div id="jw-container" class="player-view active">
        <div id="jw-player"></div>
    </div>
    
    <div id="clappr-container" class="player-view">
        <div id="clappr-player"></div>
    </div>
</div>

<script>
    function getParameterByName(name) {
        name = name.replace(/[\[\]]/g, "\\$&");
        const regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)");
        const results = regex.exec(window.location.href);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    const streams = {
        "KTV_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/1c406d282d7446349fcc0f4a1b2180d7/KTVHD_P_IN_index.mpd",
            clearkeys: { "351e547391bb45cbac66d2cb9ec0c294": "3bd646753f4903eee3b404646c7819d3" }
        },
        "SUNTV_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream2/803a6a4872a449a193b5846b5d101359/SunTVHD_P_IN_index.mpd",
            k1: "3891557f1cb14dedb7545bf52499d748", k2: "fb662f742e5f5e0c61a7c1c66d2b019a"
        },
        "SUN_MUSIC_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/f6f92acd2b4f4870aabf04b274c69376/SunMusicHD_P_IN_index.mpd",
            k1: "b5a2c6d13b9748de9ceebc0a8adc8af3", k2: "e806fec1bf1c8a844216118c94bad020"
        },
        "SUN_LIFE": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/c7d23c2ad1084c9794498e51158a2158/SunLife_IN_index.mpd",
            k1: "81546df3f41c4a6dbc9a4efc7f2fb626", k2: "3928505f4054cf1fa935276fdbe40992"
        },
        "ADITHYA_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/b1865a87b7384835aee1a02038bce2ef/AdithyaTV_IN_index.mpd",
            k1: "d674a1a7f43641a29bd8867d87c7259a", k2: "812f55dcde68619fc6ad95951b241d2c"
        },
        "CHUTTI_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/e3eccb3f250a4ca8826e42914b2322a6/ChuttiTV_IN_index.mpd",
            k1: "05da38a46fb7403088f41434e44de980", k2: "488046139a1e1d65323cfe4bb1b30b7b"
        },
        "SUN_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream1/83c3f9aa91eb425aa899cd7d5f62f4cb/SunTV_IN_index.mpd",
            k1: "6752015acf084572a08dfe21796f8b45", k2: "ff823ddbe5625c35d3e93f0ed4520115"
        },
        "KTV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/328190ac960b4aacb94e5475747875b7/KTV_IN_index.mpd",
            k1: "426117d115b04497b0b0d425e8095184", k2: "aa751ae8a41ac6f87141734163ffe3b2"
        },
        "SUN_MUSIC": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/168833cab00b4850be1a036c5c5e9850/SunMusic_IN_index.mpd",
            k1: "21ddc14c4da94c079d4f4c343ecdcd80", k2: "5701bee4ee9b625d0c8ed7de032a7478"
        },
        "GEMINI_TV_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream14/6a5c3dbb8b6044f8be835e13a815f40b/GeminiTVHD_P_IN_index.mpd",
            k1: "880dc94460af4197bbbf43a176fb3a95", k2: "0beb7012ffd133360889d5d56e20de4d"
        },
        "GEMINI_TV_HD_DOLBY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream14/e778d9c98488494b9c9b38f9c48b63ec/GeminiTVHDB_IN_index.mpd",
            k1: "880dc94460af4197bbbf43a176fb3a95", k2: "0beb7012ffd133360889d5d56e20de4d"
        },
        "GEMINI_MUSIC_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/3a9f4a8ecfee4d99aca372b763943c72/GeminiMusicHD_P_IN_index.mpd",
            k1: "76230567f3c04513a7e5d1249ab65983", k2: "4ee6dc9a99d894dc41b0878d6ea22790"
        },
        "GEMINI_COMEDY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/b7f0913638684933ae78f7abc79baa20/GeminiComedy_IN_index.mpd",
            k1: "93e686ceac134f30b0a7bc3ce5a76b26", k2: "2c37e97fa0646751e1f2b85bc4b9ff8a"
        },
        "GEMINI_LIFE": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/af3c022825d04c21a532d80c370a42d2/GeminiLife_IN_index.mpd",
            k1: "96d5157791ea4817a66a419e285a137f", k2: "d6d0ad2a9a6cc56e18d7557c7c693a37"
        },
        "KUSHI_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/b24f908d8104462db019c91dac6512a3/KushiTV_IN_index.mpd",
            k1: "e16421cf7c374c57a8f7e91049f58cd9", k2: "b7e01aba2c307b1f05057e91a9d150d2"
        },
        "GEMINI_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream4/a1a61fa1811c4d20a5c2d5e14cdc0cd2/GeminiTVB_IN_index.mpd",
            k1: "2ec6fa5b77ff4223a376c2b98032fbf8", k2: "afe96f602fd6abedcd9c2c8cdf799afd"
        },
        "GEMINI_MOVIES": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/8fc1247bb1a24a21b45f2635eda1dc07/GeminiMovies_IN_index.mpd",
            k1: "0c37231880034787bce9fd3607aa09ea", k2: "e063bb30351dac572bac24ed43d304b2"
        },
        "GEMINI_MOVIES_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream17/ec0d4961a002442295f91efc9d675c9d/GeminiMoviesHDB_IN_index.mpd",
            k1: "0c37231880034787bce9fd3607aa09ea", k2: "e063bb30351dac572bac24ed43d304b2"
        },
        "GEMINI_MUSIC": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/565bc045df62411fba013c41cbb5f4c2/GeminiMusic_IN_index.mpd",
            k1: "0faa2e9469fc45fdaf1728333351ec71", k2: "ffcf9dee6ede62bd8740dacfaa0c1e59"
        },
        "SURYA_TV_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream15/d719fad367614ee5baad747822767ad8/SuryaTVHDB_IN_index.mpd",
            k1: "eae838ccd75d4a1fbff6fd7dd1c97780", k2: "8259ce0c112725a4d2c94d154207425f"
        },
        "SURYA_TV_HD_DOLBY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream15/d719fad367614ee5baad747822767ad8/SuryaTVHDB_IN_index.mpd",
            k1: "eae838ccd75d4a1fbff6fd7dd1c97780", k2: "8259ce0c112725a4d2c94d154207425f"
        },
        "SURYA_MOVIES": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/e3b12a222dd341d8b64e2d2ec3432d31/SuryaMovies_IN_index.mpd",
            k1: "6b67bccef7024f2da29b42e10dc13f89", k2: "2e8460c47d3f01693e193dba5963a5e1"
        },
        "SURYA_MUSIC": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/e18abccfb07049ee83a79c8a38fe159a/SuryaMusic_IN_index.mpd",
            k1: "25a1d2a4c3f848b1aed911ad691fe232", k2: "3c8b2cf8611c343e6a231b6a9c7c8b58"
        },
        "SURYA_COMEDY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/074cc4c933d645eda0260950f3ce4f7b/SuryaComedy_IN_index.mpd",
            k1: "11563b00a46b43f2a0f80ecf42a4fb77", k2: "9bad28ad6f23dbb917c63ee680f66a1f"
        },
        "KOCHU_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/822c2d513c6e4236859292d9a6b209ec/KochuTV_IN_index.mpd",
            k1: "7354fb333b0c4159bc6c433c4db13d0f", k2: "fbf8b4a11febf7d2eed2283006979176"
        },
        "SURYA_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream6/291e70c33516425da1c78c90e08e9f89/SuryaTV_IN_index.mpd",
            k1: "56e1f5b5b72e4e45a98b6f287c265ab9", k2: "6dee8663e63cc8f8dda8478b8b2f3b71"
        },
        "UDAYA_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream16/3a36700e8a904726b253cf6873bfaf40/UdayaTVHD_P_IN_index.mpd",
            k1: "91b5f2d0205c4527b7aa3e41f35e1e7f", k2: "66ddb1a017753f966e20442ab2f91f18"
        },
        "UDAYA_HD_DOLBY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream16/a8d28f18944c4946ad7133938860e7cf/UdayaTVHDB_IN_index.mpd",
            k1: "91b5f2d0205c4527b7aa3e41f35e1e7f", k2: "66ddb1a017753f966e20442ab2f91f18"
        },
        "UDAYA_MOVIES": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/c4d973ab48d84c109695a7fd57164d87/UdayaMovies_IN_index.mpd",
            k1: "b4dbffb517824732a955ca02dd6aacd9", k2: "83c2aa2946432f1ded7c049efe79feef"
        },
        "UDAYA_MUSIC": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/17ecf8decebe416cabfc1060981837f7/UdayaMusic_IN_index.mpd",
            k1: "9ac3472b0040459cab52035ead6fe1ae", k2: "ba9fe9dd1b8e5421e2f8c9d23bd86922"
        },
        "UDAYA_COMEDY": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/037a75604a2c4ae1b494409eef2e6033/UdayaComedy_IN_index.mpd",
            k1: "71329783aeb74e6e9e1014fb9e4c30f4", k2: "1e025a9ed7e50fa35b8de2c96cccdd6b"
        },
        "CHINTU_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream/29a1b9de2fbc44d0ac8f569328733f3f/ChintuTV_IN_index.mpd",
            k1: "19d8a5cc002f411b89b33925acdc33e0", k2: "2a9aa7a3f69834c4f348430cc3f658bb"
        },
        "UDAYA_TV": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream5/a14a058171cb46b49c38ddbb80cd71fe/UdayaTV_IN_index.mpd",
            k1: "3084683c80234b6bbf69abfd5bb258a0", k2: "4ae6b547e5c8f51329a12d7953ee4c72"
        },
        "SUN_BANGLA": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream7/9876418f56d048879014e243fc0cf2d9/SunBangla_IN_index.mpd",
            k1: "01f7b9f7bf7e425f86d6dfd478390e3f", k2: "5fde68100a7856d055038236ffc7c84a"
        },
        "SUN_MARATHI": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream9/350cc0a44cdb400a8110de68afd85161/SunMarathi_IN_index.mpd",
            k1: "5ea90a1f3b1e4a0a9b72c8e0f4a9bf31", k2: "30899fda5ca4dfb5a4535ce10c4d7341"
        },
        "SUN_MARATHI_AU": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream9/2699d3c42a1944d9bb8e919433a349c2/SunMarathiB_AU_index.mpd",
            k1: "5ea90a1f3b1e4a0a9b72c8e0f4a9bf31", k2: "30899fda5ca4dfb5a4535ce10c4d7341"
        },
        "SUN_NEO_HD": {
            url: "https://white-paper-38cb.keralive.workers.dev/livestream8/2082ee4606184c17943d48478136f244/SunNeoHD_P_IN_index.mpd",
            k1: "09ffaaff477d490abb4516b7e0711d35", k2: "759cc157a993e8a76ff4d675e34b5400"
        }
    };

    // k1, k2 ക്ലിയർ കീകളിലേക്ക് മാറ്റുന്നു
    for (const key in streams) {
        const s = streams[key];
        if (!s.clearkeys && s.k1 && s.k2) {
            s.clearkeys = {};
            s.clearkeys[s.k1] = s.k2;
        }
    }

    let clapprPlayerInstance = null;
    let jwPlayerInstance = null;
    let currentStreamKey = null;

    const mainContainer = document.getElementById("main-container");
    const switcherMenu = document.getElementById('switcher-menu');
    const switcher = document.getElementById('choose-player');
    let hideTimeout;

    // Retry Watchdog State (തടസ്സമില്ലാതെ പ്ലേ ചെയ്യാനുള്ള ലോജിക്)
    const retryState = {
        tries: 0,
        maxTries: 6,
        baseDelayMs: 1500,
        lastAttemptAt: 0,
        bufferingTimer: null,
        bufferingTimeoutMs: 8000
    };

    function resetRetryState() {
        retryState.tries = 0;
        retryState.lastAttemptAt = 0;
        clearTimeout(retryState.bufferingTimer);
        retryState.bufferingTimer = null;
    }

    function scheduleRetry(streamKey) {
        retryState.tries++;
        if (retryState.tries > retryState.maxTries) {
            console.error("Max retries reached. Giving up.");
            showErrorMessage("Unable to play stream. Please try again later.");
            return;
        }
        const delay = retryState.baseDelayMs * Math.pow(2, retryState.tries - 1);
        console.log(`Retry #${retryState.tries} in ${delay}ms`);
        retryState.lastAttemptAt = Date.now();
        setTimeout(() => {
            routePlayback(streamKey, true);
        }, delay);
    }

    function showErrorMessage(msg) {
        document.getElementById("jw-container").innerHTML = `<p style="color:white;text-align:center;padding-top:45vh">${msg}</p>`;
        document.getElementById("clappr-container").innerHTML = `<p style="color:white;text-align:center;padding-top:45vh">${msg}</p>`;
    }

    // UI AUTO-HIDE LOGIC
    function hideControls() {
        if (document.activeElement === switcher) return;
        switcherMenu.classList.add('hidden');
        mainContainer.style.cursor = 'none'; 
    }

    function showControls() {
        switcherMenu.classList.remove('hidden');
        mainContainer.style.cursor = 'default'; 
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(hideControls, 3000); 
    }

    // പ്ലെയറുകൾ ക്ലീൻ ചെയ്യാനുള്ള ഫങ്ഷൻ
    function destroyAllPlayers() {
        try {
            if (jwPlayerInstance) { jwPlayerInstance.remove(); jwPlayerInstance = null; }
            if (clapprPlayerInstance) { clapprPlayerInstance.destroy(); clapprPlayerInstance = null; }
        } catch (e) { console.warn("Error destroying players:", e); }
    }

    // സ്ട്രീമുകളെ കറക്റ്റ് പ്ലെയറിലേക്ക് അയക്കുന്ന റൂട്ടിങ് സിസ്റ്റം
    function routePlayback(streamKey, isRetry = false) {
        const stream = streams[streamKey];
        if (!stream || !stream.url || !stream.clearkeys) {
            showErrorMessage("Invalid or missing stream ID.");
            return;
        }

        currentStreamKey = streamKey;
        if (!isRetry) resetRetryState();

        destroyAllPlayers();

        const activePlayer = switcher.value;
        if (activePlayer === 'jw') {
            initJWPlayer(streamKey, stream);
        } else {
            initClapprPlayer(streamKey, stream);
        }
    }

    /* ---------- 1. DYNAMIC JW PLAYER + RETRY WATCHDOG ---------- */
    function initJWPlayer(streamKey, stream) {
        document.getElementById('jw-container').innerHTML = '<div id="jw-player"></div>';
        clearTimeout(retryState.bufferingTimer);

        let config = {
            file: stream.url,
            type: "dash",
            stretching: "exactfit", // 🚀 സ്ട്രെച്ച് ചെയ്ത് സ്ക്രീനിൽ ഫിറ്റ് ആക്കുന്നു
            preload: "auto",
            autostart: true,
            mute: true,
            width: "100%",
            height: "100%",
            drm: { clearkey: { keyId: Object.keys(stream.clearkeys)[0], key: Object.values(stream.clearkeys)[0] } },
            dash: { shakaConfig: { streaming: { rebufferingGoal: 1, bufferingGoal: 8, bufferBehind: 5 } } },
            mediacontrol: { seekbar: "#e92065", buttons: "#e92065" }
        };

        jwPlayerInstance = jwplayer("jw-player").setup(config);

        jwPlayerInstance.on('ready', function() {
            let playerDiv = document.getElementById('jw-player');
            playerDiv.addEventListener('click', showControls);
            playerDiv.addEventListener('dblclick', function() {
                let isFull = jwPlayerInstance.getFullscreen();
                jwPlayerInstance.setFullscreen(!isFull);
            });
        });

        jwPlayerInstance.on('userActive', showControls);

        // JW Player Error Recovery
        jwPlayerInstance.on("error", function(e) {
            console.warn("JW Playback Error:", e.message);
            destroyAllPlayers();
            scheduleRetry(streamKey);
        });

        // Buffering Timeout Watchdog (8 സെക്കൻഡ് ബഫറിങ് ഉണ്ടായാൽ റീലോഡ് ചെയ്യും)
        jwPlayerInstance.on("buffer", function() {
            clearTimeout(retryState.bufferingTimer);
            retryState.bufferingTimer = setTimeout(() => {
                console.warn("JW Buffering timeout reached — attempting recovery");
                destroyAllPlayers();
                scheduleRetry(streamKey);
            }, retryState.bufferingTimeoutMs);
        });

        jwPlayerInstance.on("play", function() {
            clearTimeout(retryState.bufferingTimer);
            retryState.bufferingTimer = null;
            resetRetryState();
        });
    }

    /* ---------- 2. DYNAMIC CLAPPR PLAYER + RETRY WATCHDOG ---------- */
    function initClapprPlayer(streamKey, stream) {
        document.getElementById('clappr-player').innerHTML = "";
        clearTimeout(retryState.bufferingTimer);

        let clapprConfig = {
            parentId: "#clappr-player",
            source: stream.url,
            autoPlay: true,
            mute: false,
            width: "100%",
            height: "100%",
            plugins: [DashShakaPlayback, LevelSelector],
            levelSelectorConfig: { title: 'Quality' },
            shakaConfiguration: { drm: { clearKeys: stream.clearkeys } },
            playback: {
                shakaConfiguration: { drm: { clearKeys: stream.clearkeys } },
                drm: { clearKeys: stream.clearkeys }
            },
            mediacontrol: { seekbar: "#FF5F15", buttons: "#D6D3D1" }
        };

        clapprPlayerInstance = new Clappr.Player(clapprConfig);

        // Clappr Recovery Watchdog Listeners
        setTimeout(() => {
            if (!clapprPlayerInstance) return;

            clapprPlayerInstance.listenTo(clapprPlayerInstance.core, Clappr.Events.CORE_PLAYBACK_ERROR, (evt) => {
                console.warn("Clappr CORE_PLAYBACK_ERROR", evt);
                destroyAllPlayers();
                scheduleRetry(streamKey);
            });

            clapprPlayerInstance.listenTo(clapprPlayerInstance.core, "playback:error", (evt) => {
                console.warn("Clappr playback:error", evt);
                destroyAllPlayers();
                scheduleRetry(streamKey);
            });

            clapprPlayerInstance.listenTo(clapprPlayerInstance.core, "playback:buffering", () => {
                clearTimeout(retryState.bufferingTimer);
                retryState.bufferingTimer = setTimeout(() => {
                    console.warn("Clappr Buffering timeout reached — recovering");
                    destroyAllPlayers();
                    scheduleRetry(streamKey);
                }, retryState.bufferingTimeoutMs);
            });

            clapprPlayerInstance.listenTo(clapprPlayerInstance.core, "playback:playing", () => {
                clearTimeout(retryState.bufferingTimer);
                retryState.bufferingTimer = null;
                resetRetryState();
            });

            clapprPlayerInstance.listenTo(clapprPlayerInstance.core, "playback:stalled", () => {
                console.warn("Clappr playback stalled — recovering");
                destroyAllPlayers();
                scheduleRetry(streamKey);
            });
        }, 250);
    }

    // SWITCHER UI CHANGE LISTENER
    switcher.addEventListener('change', function() {
        const jwContainer = document.getElementById('jw-container');
        const clapprContainer = document.getElementById('clappr-container');

        if (this.value === 'jw') {
            clapprContainer.classList.remove('active');
            jwContainer.classList.add('active');
        } else {
            jwContainer.classList.remove('active');
            clapprContainer.classList.add('active');
        }

        if (currentStreamKey) {
            routePlayback(currentStreamKey);
        }
        this.blur();
        showControls();
    });

    // MOUSE & TOUCH CONTROLS
    window.addEventListener('mousemove', showControls);
    window.addEventListener('mousedown', showControls);
    window.addEventListener('keydown', showControls);
    window.addEventListener('touchstart', showControls);
    mainContainer.addEventListener('mouseleave', hideControls);

    window.addEventListener('online', () => {
        if (!jwPlayerInstance && !clapprPlayerInstance && currentStreamKey) {
            scheduleRetry(currentStreamKey);
        }
    });

    // START PLAYBACK
    const id = getParameterByName('id');
    if (id && streams[id]) {
        routePlayback(id);
        showControls();
    } else {
        console.error("No valid stream id provided in URL");
        showErrorMessage("Invalid or missing stream ID.");
    }
</script>
<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496" integrity="sha512-ZE9pZaUXND66v380QUtch/5sE9tPFh2zg45pR2PB0CVkCtOREv2AJKkSidISWkysEuQ0EH8faUU5du78bx87UQ==" data-cf-beacon='{"version":"2024.11.0","token":"5d01f91227eb4a92b234f40dd5a51592","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>
</html>
