# Current load example

````json
[
  {
    "name": "Crawler_0/neutral_test",
    "description": "Crawler created through py",
    "active": 1,
    "testing": 0,
    "agent": [
      {
        "name": "Agent_0/neutral_test",
        "preferences": [
          {
            "name": "asdf",
            "value": "US-CO-COLORADO_SPRINGS"
          },
          {
            "name": "asdf",
            "value": null
          }
        ],
        "description": "This is the agent",
        "location": "US-CO-COLORADO_SPRINGS",
        "identification": "MultiLogin",
        "multilogin_id": "",
        "multilogin_profile": {
          "name": "%%AGENTID%%",
          "os": "win",
          "browser": "mimic",
          "network": {
            "proxy": {
              "type": "%%PROXYTYPE%%",
              "host": "%%PROXYHOST%%",
              "port": "%%PROXYPORT%%",
              "username": "%%PROXYUSERNAME%%",
              "password": "%%PROXYPASSWORD%%"
            },
            "dns": []
          },
          "navigator": {
            "language": "es-MX,es;q=0.8,en-US;q=0.5,en;q=0.3",
            "doNotTrack": 0
          },
          "geolocation": {
            "mode": "BLOCK"
          },
          "canvas": {
            "mode": "BLOCK"
          },
          "storage": {
            "local": true
          }
        }
      }
    ],
    "configuration": [
      {
        "name": "Config/neutral",
        "description": null,
        "preferences": [
          {
            "name": "location",
            "value": "US-CO-COLORADO_SPRINGS"
          },
          {
            "name": "history",
            "value": null
          }
        ],
        "params": [
          {
            "pi": 0.0,
            "psi": 0.0033843000867772,
            "alpha": 0.26080780865406,
            "tau": 1.0,
            "kappa": 2,
            "beta": 0.084178134402416,
            "search_terms": [],
            "media_outlet_urls": []
          }
        ]
      }
    ],
    "proxy": [
      {
        "name": "Proxy_0/neutral_test",
        "description": "Proxy",
        "type": "GEOSURF",
        "hostname": "state.geosurf.io",
        "port": 8000,
        "username": "000000",
        "password": "CHANGEME-password"
      }
    ],
    "experiment_id": 1,
    "id": 6,
    "queues": [
      {
        "name": "Session_0",
        "description": "A single browser session",
        "start_at": "2021-03-26T09:30:12.057931-06:00",
        "jobs": [
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 10 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 10 seconds.",
                "value": 10
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.google.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.google.com"
              }
            ]
          },
          {
            "name": "Enter Text",
            "type": "entertextfieldjob",
            "description": "Enter text into the defined field",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_benign ",
                "value": "search_google_benign"
              },
              {
                "name": "flag",
                "description": "",
                "value": "benign"
              },
              {
                "name": "text",
                "description": "Text to enter",
                "value": "restaurants near me"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": "//input[@name='q']"
              },
              {
                "name": "appendReturn",
                "description": "Optional field: appends a return key after the text given if true.",
                "value": true
              },
              {
                "name": "typingMode",
                "description": "Optional field: How the text is entered into the field",
                "value": "SIMULATED_FIXINGTYPOS"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 5 seconds.",
                "value": 5
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll up",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 100.0 of window.",
                "value": 100.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll up",
                "value": "UP"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 6 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 6 seconds.",
                "value": 6
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.ebay.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.ebay.com"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 70 seconds.",
                "value": 70
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 10 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 10 seconds.",
                "value": 10
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.ca.gov",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.ca.gov"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 70 seconds.",
                "value": 70
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 3 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 3 seconds.",
                "value": 3
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.google.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.google.com"
              }
            ]
          },
          {
            "name": "Enter Text",
            "type": "entertextfieldjob",
            "description": "Enter text into the defined field",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_neutral ",
                "value": "search_google_neutral"
              },
              {
                "name": "flag",
                "description": "",
                "value": "neutral"
              },
              {
                "name": "text",
                "description": "Text to enter",
                "value": "Lakers"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": "//input[@name='q']"
              },
              {
                "name": "appendReturn",
                "description": "Optional field: appends a return key after the text given if true.",
                "value": true
              },
              {
                "name": "typingMode",
                "description": "Optional field: How the text is entered into the field",
                "value": "SIMULATED_FIXINGTYPOS"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 5 seconds.",
                "value": 5
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll up",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 100.0 of window.",
                "value": 100.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll up",
                "value": "UP"
              }
            ]
          },
          {
            "name": "Select",
            "type": "singleselecturljob",
            "description": "Select an item and click",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_neutral ",
                "value": "search_google_neutral"
              },
              {
                "name": "flag",
                "description": "",
                "value": "neutral"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": ".//div[@class=\"yuRUbf\"]/a"
              },
              {
                "name": "decisionType",
                "description": "Decision type for choosing an item out of the list given by the selector is CALCULATED",
                "value": "CALCULATED"
              },
              {
                "name": "flag",
                "description": "",
                "value": "CALCULATED/neutral"
              },
              {
                "name": "task",
                "description": "This job is part of the task CALCULATED/search_google_neutral ",
                "value": "CALCULATED/search_google_neutral"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 80.0 of window.",
                "value": 80.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          }
        ]
      },
      {
        "name": "Session_0",
        "description": "A single browser session",
        "start_at": "2021-03-26T09:30:12.057931-06:00",
        "jobs": [
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 10 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 10 seconds.",
                "value": 10
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.google.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.google.com"
              }
            ]
          },
          {
            "name": "Enter Text",
            "type": "entertextfieldjob",
            "description": "Enter text into the defined field",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_benign ",
                "value": "search_google_benign"
              },
              {
                "name": "flag",
                "description": "",
                "value": "benign"
              },
              {
                "name": "text",
                "description": "Text to enter",
                "value": "restaurants near me"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": "//input[@name='q']"
              },
              {
                "name": "appendReturn",
                "description": "Optional field: appends a return key after the text given if true.",
                "value": true
              },
              {
                "name": "typingMode",
                "description": "Optional field: How the text is entered into the field",
                "value": "SIMULATED_FIXINGTYPOS"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 5 seconds.",
                "value": 5
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll up",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 100.0 of window.",
                "value": 100.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll up",
                "value": "UP"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 6 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 6 seconds.",
                "value": 6
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.ebay.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.ebay.com"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 70 seconds.",
                "value": 70
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 10 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 10 seconds.",
                "value": 10
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.ca.gov",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.ca.gov"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 70 seconds.",
                "value": 70
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Wait",
            "type": "waitjob",
            "description": "Wait for 3 seconds",
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait for 3 seconds.",
                "value": 3
              }
            ]
          },
          {
            "name": "Visit",
            "type": "visitjob",
            "description": "Visit https://www.google.com",
            "behaviors": [
              {
                "name": "url",
                "description": "URL",
                "value": "https://www.google.com"
              }
            ]
          },
          {
            "name": "Enter Text",
            "type": "entertextfieldjob",
            "description": "Enter text into the defined field",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_neutral ",
                "value": "search_google_neutral"
              },
              {
                "name": "flag",
                "description": "",
                "value": "neutral"
              },
              {
                "name": "text",
                "description": "Text to enter",
                "value": "Lakers"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": "//input[@name='q']"
              },
              {
                "name": "appendReturn",
                "description": "Optional field: appends a return key after the text given if true.",
                "value": true
              },
              {
                "name": "typingMode",
                "description": "Optional field: How the text is entered into the field",
                "value": "SIMULATED_FIXINGTYPOS"
              }
            ]
          },
          {
            "name": "Scroll by duration",
            "type": "scrollbydurationjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "scrollDuration",
                "description": "Scroll for 5 seconds.",
                "value": 5
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll up",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 100.0 of window.",
                "value": 100.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll up",
                "value": "UP"
              }
            ]
          },
          {
            "name": "Select",
            "type": "singleselecturljob",
            "description": "Select an item and click",
            "behaviors": [
              {
                "name": "task",
                "description": "This job is part of the task search_google_neutral ",
                "value": "search_google_neutral"
              },
              {
                "name": "flag",
                "description": "",
                "value": "neutral"
              },
              {
                "name": "selectionType",
                "description": "Type of selection",
                "value": "XPATH"
              },
              {
                "name": "selector",
                "description": " selector",
                "value": ".//div[@class=\"yuRUbf\"]/a"
              },
              {
                "name": "decisionType",
                "description": "Decision type for choosing an item out of the list given by the selector is CALCULATED",
                "value": "CALCULATED"
              },
              {
                "name": "flag",
                "description": "",
                "value": "CALCULATED/neutral"
              },
              {
                "name": "task",
                "description": "This job is part of the task CALCULATED/search_google_neutral ",
                "value": "CALCULATED/search_google_neutral"
              }
            ]
          },
          {
            "name": "Scroll by length",
            "type": "scrollbylengthjob",
            "description": "Scroll down",
            "behaviors": [
              {
                "name": "length",
                "description": "Scroll 80.0 of window.",
                "value": 80.0
              },
              {
                "name": "scrollDirection",
                "description": "Scroll down",
                "value": "DOWN"
              }
            ]
          }
        ]
      }
    ]
  }
]

````
