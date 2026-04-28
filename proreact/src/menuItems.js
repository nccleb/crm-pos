export const menuItems = [
    {
      title: "Home",
      index:1
    },
    {
      title: "Services",
      submenu: [
        {
          title: "shop",
          index:2
        },
        {
          title: "web development",
          submenu: [
            {
              title: "Frontend",
              index:3
            },
            {
              title: "Backend",
              submenu: [
                {
                  title: "NodeJS",
                  index:5
                },
                {
                  title: "PHP",
                  index:6
                },
              ],
            },
          ],
        },
        {
          title: "SEO",
          index:7
        },
      ],
    },
    {
      title: "About",
      submenu: [
        {
          title: "Who we are",
          index:8
        },
        {
          title: "Our values",
          index:9
        },
      ],
    },
  ];